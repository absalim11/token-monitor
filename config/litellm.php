<?php

return [
    'api_url' => env('LITELLM_API_URL', 'https://litellm-api.up.railway.app'),
    'api_key' => env('LITELLM_API_KEY'),
    'cache_ttl' => env('CACHE_TTL', 5),
    'timeout' => 30,
    'retry_times' => 3,
    'retry_delay' => 1000,
];
