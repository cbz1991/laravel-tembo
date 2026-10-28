<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API key
    |--------------------------------------------------------------------------
    |
    | Create a key in the Tembo dashboard under Settings > API Keys. It is sent
    | as a Bearer token on every request. Use Tembo::withApiKey() to call the
    | API with a different key at runtime (for example one key per tenant).
    |
    */

    'api_key' => env('TEMBO_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */

    'base_url' => env('TEMBO_BASE_URL', 'https://api.tembo.io'),

    /*
    |--------------------------------------------------------------------------
    | HTTP options
    |--------------------------------------------------------------------------
    |
    | timeout: seconds to wait for a response.
    |
    | retries: how many extra attempts a request gets. A request is retried
    | when Tembo answers 429 (Too Many Requests, honouring Retry-After), and,
    | for GET requests only, on 5xx answers and connection errors. Writes are
    | never retried after a 5xx or a dropped connection, because the first
    | attempt may already have started an agent.
    |
    | retry_delay: base delay in milliseconds between attempts; it doubles on
    | every attempt.
    |
    */

    'timeout' => (int) env('TEMBO_TIMEOUT', 30),

    'retries' => (int) env('TEMBO_RETRIES', 2),

    'retry_delay' => (int) env('TEMBO_RETRY_DELAY', 500),

    /*
    |--------------------------------------------------------------------------
    | Default headers and query parameters
    |--------------------------------------------------------------------------
    |
    | Sent with every request. Tembo::withHeaders() and Tembo::withQuery()
    | add to them at runtime; parameters passed to a method take precedence.
    |
    */

    'headers' => [],

    'query' => [],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | log: the lowest level to log, like the official SDK's TEMBO_LOG: "debug"
    | (every request with its status and duration, plus retries), "info"
    | (retries), "warning" (failed requests), "error" (server and connection
    | errors) or null to log nothing. Request bodies and the API key are never
    | logged.
    |
    | log_channel: the Laravel log channel to write to; null uses the default.
    |
    */

    'log' => env('TEMBO_LOG'),

    'log_channel' => env('TEMBO_LOG_CHANNEL'),

    /*
    |--------------------------------------------------------------------------
    | Watching sessions
    |--------------------------------------------------------------------------
    |
    | Tembo has no webhooks yet, so Tembo::sessions()->watch($id) dispatches a
    | queued job that polls the session and fires SessionCompleted,
    | SessionFailed or SessionCancelled when it finishes (or
    | SessionWatchTimedOut when it takes longer than "timeout" seconds).
    |
    */

    'watch' => [
        'interval' => (int) env('TEMBO_WATCH_INTERVAL', 30),
        'timeout' => (int) env('TEMBO_WATCH_TIMEOUT', 3600),
        'connection' => env('TEMBO_QUEUE_CONNECTION'),
        'queue' => env('TEMBO_QUEUE'),
    ],

];
