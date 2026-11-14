<?php

/*
| Soil-based fertilizer guidance (F15): limits for attempts and retries.
| The AI provider settings (key, model, timeout) are in config/services.php under "gemini".
*/
return [

    // How many times one request may be sent to the AI service in total (first try + retries).
    'max_attempts' => (int) env('SOIL_GUIDANCE_MAX_ATTEMPTS', 3),

    // Seconds the farmer must wait after an attempt before pressing "Try again".
    'retry_cooldown_seconds' => (int) env('SOIL_GUIDANCE_RETRY_COOLDOWN', 30),

    // A request still "processing" after this many seconds has got stuck (for example the server stopped).
    // It may then be tried again. Must be longer than the AI request timeout.
    'stale_after_seconds' => (int) env('SOIL_GUIDANCE_STALE_AFTER', 120),

];
