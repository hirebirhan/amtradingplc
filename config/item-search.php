<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Item Search Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('ITEM_SEARCH_CACHE_ENABLED', true),
        'ttl' => env('ITEM_SEARCH_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'item_search',
    ],

    'search' => [
        'min_length' => env('ITEM_SEARCH_MIN_LENGTH', 2),
        'result_limit' => env('ITEM_SEARCH_RESULT_LIMIT', 15),
        'debounce_ms' => env('ITEM_SEARCH_DEBOUNCE_MS', 300),
    ],

    'stock' => [
        'low_threshold' => env('ITEM_LOW_STOCK_THRESHOLD', 10),
        'show_warnings' => env('ITEM_SHOW_STOCK_WARNINGS', true),
    ],
];