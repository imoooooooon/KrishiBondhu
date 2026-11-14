<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An AI guidance attempt did not produce a usable answer. Thrown by GeminiClient and SoilGuidanceService.
 *
 * $errorCode is one of the constants below: a short, safe word that is saved on the record and turned into a
 * simple message for the farmer. $context holds technical details for the server log only (HTTP status,
 * the provider's own code). It never contains the API key, the farmer's text or the photo.
 */
class AiGuidanceException extends RuntimeException
{
    public const NOT_CONFIGURED = 'not_configured';           // no API key or model in .env

    public const INVALID_CREDENTIALS = 'invalid_credentials'; // the key was refused (HTTP 401 / 403)

    public const MODEL_UNAVAILABLE = 'model_unavailable';     // the configured model does not exist (HTTP 404)

    public const RATE_LIMITED = 'rate_limited';               // usage limit reached (HTTP 429)

    public const TIMEOUT = 'timeout';                         // no answer within the time limit

    public const NETWORK = 'network';                         // could not connect at all

    public const PROVIDER_ERROR = 'provider_error';           // any other error answer from the provider

    public const PROVIDER_REJECTED = 'provider_rejected';     // the provider refused or blocked the request

    public const INVALID_RESPONSE = 'invalid_response';       // an answer arrived but it is not usable

    public const PHOTO_MISSING = 'photo_missing';             // the saved photo could not be read

    public const UNEXPECTED = 'unexpected';                   // a bug on our side; see the log

    /** @param  array<string, mixed>  $context */
    public function __construct(public readonly string $errorCode, public readonly array $context = [])
    {
        parent::__construct('AI guidance failed: '.$errorCode);
    }
}
