<?php

namespace App\Services;

use App\Exceptions\AiGuidanceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * The only class that talks to the Gemini API (Google). Everything specific to the provider is here:
 * the URL, the headers and the request and response shapes. Settings come from config/services.php ("gemini").
 *
 * It uses Gemini's Interactions API, as documented by Google in November 2026:
 *   POST {base_url}/interactions
 *   headers: x-goog-api-key (the key), Api-Revision
 *   body:    model, system_instruction (string), input (list of parts), response_format (JSON + schema), store
 *   answer:  status ("completed" when it worked), steps[] -> type "model_output" -> content[] -> type "text" -> text
 *
 * The key is sent in a header, never in the URL, so it cannot end up in logs. A photo is sent inside the
 * request as base64 ("inline"), so the private file never needs a public URL.
 *
 * Every problem becomes an AiGuidanceException with a safe error code. This class does not know about
 * soil, farmers or the database: SoilGuidanceService decides what to ask and what to do with the answer.
 */
class GeminiClient
{
    public function model(): string
    {
        return (string) config('services.gemini.model');
    }

    /**
     * Ask the model for one JSON answer that follows $schema.
     *
     * @param  string  $instructions  our fixed rules for the model (the "system instruction")
     * @param  string  $prompt  the text for this request
     * @param  array<string, mixed>  $schema  JSON Schema the answer must follow
     * @param  array{bytes: string, mime: string}|null  $image  an optional photo
     * @return string the JSON text written by the model (not checked yet)
     *
     * @throws AiGuidanceException
     */
    public function generateJson(string $instructions, string $prompt, array $schema, ?array $image = null): string
    {
        $config = config('services.gemini');

        if (blank($config['api_key'] ?? null) || blank($config['model'] ?? null)) {
            throw new AiGuidanceException(AiGuidanceException::NOT_CONFIGURED);
        }

        // Google's guide: with one image, put the text first and the image after it.
        $input = [['type' => 'text', 'text' => $prompt]];
        if ($image !== null) {
            $input[] = ['type' => 'image', 'mime_type' => $image['mime'], 'data' => base64_encode($image['bytes'])];
        }

        $headers = ['x-goog-api-key' => $config['api_key']];
        if (filled($config['api_revision'] ?? null)) {
            $headers['Api-Revision'] = $config['api_revision'];
        }

        try {
            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) $config['connect_timeout'])
                ->timeout((int) $config['timeout']) // bounded: the farmer's page is waiting for this
                ->post(rtrim((string) $config['base_url'], '/').'/interactions', [
                    'model' => $config['model'],
                    'system_instruction' => $instructions,
                    'input' => $input,
                    'response_format' => ['type' => 'text', 'mime_type' => 'application/json', 'schema' => $schema],
                    'generation_config' => ['max_output_tokens' => (int) $config['max_output_tokens']],
                    'store' => false, // do not keep the farmer's request on Google's servers after answering
                ]);
        } catch (ConnectionException $e) {
            // Laravel throws the same exception for "could not connect" and "took too long".
            $timedOut = Str::contains($e->getMessage(), ['timed out', 'cURL error 28'], ignoreCase: true);

            throw new AiGuidanceException($timedOut ? AiGuidanceException::TIMEOUT : AiGuidanceException::NETWORK);
        }

        if ($response->failed()) {
            throw $this->failureFromHttpError($response);
        }

        return $this->textFromAnswer($response);
    }

    /** Turn an HTTP error (4xx / 5xx) into one of our safe error codes. */
    private function failureFromHttpError(Response $response): AiGuidanceException
    {
        // Google sends {"error": {...}}, sometimes wrapped in a list: [{"error": {...}}].
        $error = $response->json('error') ?? $response->json('0.error') ?? [];
        $message = is_array($error) ? (string) ($error['message'] ?? '') : '';
        $status = $response->status();

        $code = match (true) {
            $status === 401 => AiGuidanceException::INVALID_CREDENTIALS,
            $status === 403 => AiGuidanceException::ACCESS_DENIED, // e.g. "free tier is not available for this project"
            $status === 400 && Str::contains($message, 'API key', ignoreCase: true) => AiGuidanceException::INVALID_CREDENTIALS,
            $status === 404 => AiGuidanceException::MODEL_UNAVAILABLE,
            $status === 429 => AiGuidanceException::RATE_LIMITED,
            in_array($status, [408, 504], true) => AiGuidanceException::TIMEOUT,
            default => AiGuidanceException::PROVIDER_ERROR, // 400 bad request, 402 billing, 5xx server trouble
        };

        return new AiGuidanceException($code, [
            'http_status' => $status,
            'provider_code' => is_array($error) ? ($error['status'] ?? $error['code'] ?? null) : null,
            'provider_message' => Str::limit($message, 300),
        ]);
    }

    /** Take the model's text out of a successful HTTP answer. A 200 answer is not always a usable one. */
    private function textFromAnswer(Response $response): string
    {
        $body = $response->json();
        if (! is_array($body)) {
            throw new AiGuidanceException(AiGuidanceException::INVALID_RESPONSE, ['reason' => 'answer is not JSON']);
        }

        $status = $body['status'] ?? null;

        // "incomplete": the model stopped early (for example it ran out of output tokens), so the JSON is cut off.
        if ($status === 'incomplete') {
            throw new AiGuidanceException(AiGuidanceException::INVALID_RESPONSE, ['interaction_status' => $status]);
        }

        // "failed" and anything else that is not "completed": refused, blocked or broken on the provider's side.
        if ($status !== 'completed') {
            throw new AiGuidanceException(AiGuidanceException::PROVIDER_REJECTED, [
                'interaction_status' => is_string($status) ? $status : null,
                'provider_code' => data_get($body, 'errors.0.code'),
            ]);
        }

        $text = '';
        foreach ((array) ($body['steps'] ?? []) as $step) {
            if (! is_array($step) || ($step['type'] ?? null) !== 'model_output') {
                continue; // skip "thought" and other steps
            }
            foreach ((array) ($step['content'] ?? []) as $part) {
                if (is_array($part) && ($part['type'] ?? null) === 'text' && is_string($part['text'] ?? null)) {
                    $text .= $part['text'];
                }
            }
        }

        // "completed" with no text at all: the provider answered with nothing (usually a content block).
        if (trim($text) === '') {
            throw new AiGuidanceException(AiGuidanceException::PROVIDER_REJECTED, ['reason' => 'no text in the answer']);
        }

        return $text;
    }
}
