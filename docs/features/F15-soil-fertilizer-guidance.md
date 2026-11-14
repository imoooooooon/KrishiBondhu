# F15 — Soil-Based Fertilizer Guidance

Owner: Mehrab Hossain Nizami (`mugdhoo44`) · Issue: [#22](https://github.com/imoooooooon/KrishiBondhu/issues/22) · Sprint 2 ·
SRS row: R15 / F15 in [srs.md](../srs.md).

> **Living document.** Records what F15 does, the decisions behind it, and how to set it up and demonstrate it.
> Update it in the same PR as any change to F15 behaviour.

## 1. What it does

A farmer describes their crop and what their soil looks like. The server sends that description (and an optional
photo) to the Gemini API and saves the answer. The farmer sees general soil-care and fertilizer guidance and can
open it again later.

**Farmer opens the form → enters observations → optionally adds a photo → submits → gets guidance → can reopen it.**

It is guidance from ordinary observations. It is **not** a laboratory soil test: it never asks for or reports pH or
nutrient values.

Not part of F15: laboratory reports, crop disease diagnosis (F16), animal health (F17), treatment history (F19),
selling fertilizer (F25), voice input (F23), maps, or training an AI model.

## 2. Pages and routes

All routes are in `routes/modules/health.php`, behind `auth`.

| Method | URI | Route name | Page / action |
| --- | --- | --- | --- |
| `GET` | `/guidance/soil` | `guidance.soil.index` | My soil guidance: the farmer's own requests, 10 per page |
| `GET` | `/guidance/soil/create` | `guidance.soil.create` | The form |
| `POST` | `/guidance/soil` | `guidance.soil.store` | Save the observations and ask the AI service (5 per minute per account) |
| `GET` | `/guidance/soil/{record}` | `guidance.soil.show` | Result: ready, in progress, waiting or failed |
| `POST` | `/guidance/soil/{record}/retry` | `guidance.soil.retry` | Try a failed request again (6 per minute per account) |
| `GET` | `/guidance/soil/{record}/photo` | `guidance.soil.photo` | The private soil photo |

Farmers reach it from the **Soil and fertilizer guidance** card on the dashboard.

## 3. MVC map

| Layer | File | Job |
| --- | --- | --- |
| Model | `app/Models/SoilGuidanceRecord.php` | One request and its answer. Form choices, status helpers, retry rules (`canRetry`, `retryWaitSeconds`, `scopeClaimable`) |
| Migration | `database/migrations/2026_11_14_300000_create_soil_guidance_records_table.php` | The `soil_guidance_records` table |
| View | `resources/views/guidance/soil/{create,show,index}.blade.php` | Form, result, history |
| Controller | `app/Http/Controllers/SoilGuidanceController.php` | Coordinates: asks the policy, calls the service, returns a page or redirect |
| Form Request | `app/Http/Requests/SoilGuidance/StoreSoilGuidanceRequest.php` | Validates the form |
| Policy | `app/Policies/SoilGuidanceRecordPolicy.php` | Farmer role to use it; only the owner sees, retries or gets the photo |
| Service | `app/Services/SoilGuidanceService.php` | Saves the request, writes the AI instructions, claims the record, checks the answer, saves the result |
| Service | `app/Services/GeminiClient.php` | The only code that knows Gemini's URL, headers and JSON shapes |
| Exception | `app/Exceptions/AiGuidanceException.php` | The list of safe error codes |
| Config | `config/services.php` (`gemini`), `config/soil_guidance.php` | Key, model, time limit; attempt and retry limits |
| Tests | `tests/Feature/SoilGuidance/` | 89 tests; the AI service is always faked |

One sentence for the viva: **the view shows, the controller coordinates, the Form Request checks input, the policy
checks who, the model holds the data and its rules, and the services do the work with the AI provider.**

## 4. Form and validation

| Field | Rule |
| --- | --- |
| Crop name | Required text, 2–100 characters (free text: the project has no shared crop list yet) |
| Soil colour | Required: brown, dark, reddish, yellowish, other, **not sure** |
| Moisture | Required: dry, moist, waterlogged, **not sure** |
| Texture | Required: sandy, sticky, crumbly, other, **not sure** |
| Other things you noticed | Optional, up to 2,000 characters |
| Photo | Optional, one file, JPG or PNG, up to 5 MB, at most 6000 px per side and 12 megapixels |

Decisions:

- **Photo types are JPG and PNG, not WebP.** The brief suggested JPEG, PNG or WebP. The project already has one
  photo rule (F1, F6, F25) and one photo service, `CropListingPhotoService`, which accepts JPG and PNG. F15 uses that
  service unchanged, so all features behave the same. SVG, GIF, WebP, PDF and renamed scripts are rejected.
- The service proves the file is a real image, resizes it to at most 1600 px, re-encodes it as JPEG (this removes
  metadata such as the GPS position of the farm) and saves it under a random name in
  `storage/app/private/soil-guidance-photos/`. It is never in `public/`.
- The owner (`farmer_id`), status, attempt counters and AI answer are not fillable and not in the validation rules,
  so a form can never set them.
- A hidden `submission_token` stops a double click or page reload from creating two requests (and two AI calls).

## 5. The AI request

- Provider: **Gemini API**, Interactions API (`POST {base_url}/interactions`), as documented by Google in November 2026.
  Model: `GEMINI_MODEL` (default `gemini-3.5-flash-lite`, which accepts images and supports structured output).
- The key is sent in the `x-goog-api-key` header from the server. It is never in a URL, in JavaScript or in a log.
- `system_instruction`: one fixed text in `SoilGuidanceService::instructions()`. It says who the user is, that the
  input is observations and not measurements, and what the model must never do: invent pH or nutrient values, treat
  colour as proof, give exact doses, promise yield, name brands, diagnose diseases, or mention a photo that was not sent.
- `input`: the prompt from `SoilGuidanceService::prompt()`, then the photo (base64) when there is one. The farmer's
  words are inside a JSON block marked as data. No name, phone number or account detail is sent.
- `response_format`: JSON with the schema from `SoilGuidanceService::schema()`.
- `store: false`: Google is asked not to keep the request after answering.
- The answer is written in the page language of the request (English or Bangla), saved in `locale`.

Answer structure (saved in the `guidance` JSON column):

```json
{
  "observations_summary": "text",
  "general_soil_care": ["1 to 6 items"],
  "fertilizer_guidance": ["0 to 6 items"],
  "limitations": ["1 to 6 items"],
  "follow_up_questions": ["0 to 4 items"]
}
```

`SoilGuidanceService::validateGuidance()` checks the answer before it is saved: valid JSON object, all five keys,
right types, list sizes, text lengths (summary ≤ 1000, item ≤ 600 characters), no blank items. Extra keys are dropped.
Anything else is a failed attempt. **This proves the structure of the answer, not that the farming advice is
correct.** That is why the result page always shows the limitations and the notice.

## 6. Status and processing

```text
pending → processing → completed
                     → failed → processing (retry) → completed or failed
```

- Processing is **synchronous**: it runs inside the farmer's own request, because the project runs without a queue
  worker (`QUEUE_CONNECTION=sync`, see project-context.md). No worker command is needed. The AI request has a
  20-second limit (`GEMINI_TIMEOUT`), so the page cannot wait forever.
- **Claiming** (`SoilGuidanceService::claim`) is one `UPDATE … WHERE status is pending/failed AND attempts left`.
  The database does the check and the change together, so two requests arriving at the same moment cannot both
  start the same record. No transaction is open while waiting for the AI service.
- A record still `processing` after 120 seconds is treated as stuck and may be tried again.
- An old attempt that finishes late cannot overwrite a newer attempt (`finish` checks the attempt number).

## 7. Failures and retries

Every failure is saved as a short code in `error_code`; the farmer sees a simple message, never the provider's text.

| Code | When | Message idea |
| --- | --- | --- |
| `not_configured` | No key or model in `.env` | Service not set up correctly |
| `invalid_credentials` | HTTP 401 / 403, or "API key not valid" | Service not set up correctly |
| `model_unavailable` | HTTP 404 | Service not set up correctly |
| `rate_limited` | HTTP 429 | Service is busy, try in a few minutes |
| `timeout`, `network`, `provider_error` | No answer in time, no connection, HTTP 400 / 5xx | Could not generate guidance right now |
| `provider_rejected` | Status `failed`, or no text in the answer | The service could not give guidance for this request |
| `invalid_response` | Not JSON, wrong structure, or cut off (`incomplete`) | The answer could not be used |
| `photo_missing` | The saved photo file is gone | Make a new request with the photo |
| `unexpected` | A bug on our side (full error in the log) | Could not generate guidance right now |

Technical details go to `storage/logs/laravel.log` (record id, error code, HTTP status, provider code). The key,
the farmer's text and the photo are never logged.

Retry rules (the owner only, `POST`, with CSRF):

- only when failed, stuck, or saved but never sent; never when completed or being processed;
- at most `SOIL_GUIDANCE_MAX_ATTEMPTS` attempts in total (default 3);
- at least `SOIL_GUIDANCE_RETRY_COOLDOWN` seconds after the last attempt (default 30);
- a retry sends the same saved observations. To change them, the farmer makes a new request.

## 8. Access

- Guests are sent to log in. Accounts without the Farmer role get 403. A farmer with other roles too works normally.
- Another farmer gets 404 for the result page, the photo and the retry, so the response does not confirm the record exists.
- The history query starts from `$request->user()->soilGuidanceRecords()`, so it can only return the farmer's own rows.
- Administrators get no automatic access: the project has no admin rule for private farmer records yet.

## 9. Setup

```bash
php artisan migrate          # creates soil_guidance_records
```

In **your own** `.env` (never commit it):

| Variable | Meaning | Default |
| --- | --- | --- |
| `GEMINI_API_KEY` | Your key from Google AI Studio | empty: requests fail with "not set up" |
| `GEMINI_MODEL` | Model name | `gemini-3.5-flash-lite` |
| `GEMINI_TIMEOUT` | Seconds to wait for an answer | `20` |
| `SOIL_GUIDANCE_MAX_ATTEMPTS` | Attempts per request, in total | `3` |
| `SOIL_GUIDANCE_RETRY_COOLDOWN` | Seconds between attempts | `30` |

No queue worker, no new Composer package, no `storage:link` (the photo is private).

## 10. Tests

`php artisan test tests/Feature/SoilGuidance` (89 tests). Every test fakes the AI service with `Http::fake()`, and
`Http::preventStrayRequests()` makes a forgotten fake fail, so tests never need a key or use quota.

They cover: submitting with and without a photo; guests and non-farmers; required fields and allowed values;
"Not sure"; rejected uploads; ownership; other farmers (view, retry, photo); saving and showing a valid answer; the
no-photo request sending no image; the image request format; timeouts and provider errors; unusable answers; retry
with the same observations; no retry for completed or processing records; rate limits and attempt limits; single
claiming; history per farmer; escaping of AI text; long text and empty optional lists.

**What tests cannot show:** whether a real Gemini answer is good farming advice. Read real answers by eye for
invented measurements, false certainty and unclear wording. Checking some answers cannot guarantee every future one.

## 11. Known limits

- A real Gemini request must be verified with a real key before the demo (see the SRS row for the current status).
- Gemini must still be confirmed with the faculty (SRS §2.5c) and is recorded in [third-party.md](../third-party.md).
- On Gemini's free tier, Google may use request content to improve its products. The form tells the farmer that the
  observations and photo are sent to an AI service; no account details are sent.
- While guidance is generated the farmer waits on the form page (up to about 20 seconds). A queue would remove the
  wait but needs a worker, which the project does not run.
- Requests cannot be deleted by the farmer yet, so photos are kept as long as their request.
- WebP photos are not accepted (project photo rule).
- Bangla strings are a draft and need a fluent reader's check.

## 12. Demonstration

1. Log in as a farmer → dashboard → **Get soil guidance**. Fill in crop, colour, moisture, texture; no photo; submit.
2. The result page shows the summary, soil care, fertilizer guidance, limitations, questions and the notice.
3. Dashboard → **My soil guidance** → **View result** opens the same saved result.
4. **New request** with a photo. The result page shows the photo; its URL only works for this farmer.
5. Simulate a failure: set `GEMINI_MODEL=does-not-exist` (or empty `GEMINI_API_KEY`) in `.env`, submit a request.
   The page shows "Guidance could not be generated" with a simple message. Check `storage/logs/laravel.log` for the code.
6. Restore the setting, wait 30 seconds, press **Try again**: the same observations are sent and the result appears.
7. Log in as another farmer and open the first farmer's result URL and photo URL: both answer 404.
   Log in as a buyer: 403.

## 13. Questions to be ready for in the viva

- Why is the AI call in a service and not in the controller? Where would F16 reuse `GeminiClient`?
- How does `claim()` stop two requests from processing the same record? Why one `UPDATE` and not a transaction?
- Why is `farmer_id` not in `$fillable`? What would happen if it were?
- What does `validateGuidance()` prove, and what does it not prove?
- Why is the photo sent as base64 instead of a URL? Where is it stored and who can read it?
- What happens, step by step, when the key is missing? When Gemini answers 429? When the answer is not JSON?
- Why can a completed request not be retried? Where is that rule, and where is the "who" rule?
- How does the page stay safe if the AI answer contains `<script>`?
- How do the tests run without a real key?
