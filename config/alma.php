<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Region code
    |--------------------------------------------------------------------------
    */
    'region' => env('ALMA_REGION', 'eu'),

    /*
    |--------------------------------------------------------------------------
    | Institution zone settings
    |--------------------------------------------------------------------------
    */
    'iz' => [
        // API key for institution zone
        'key' => env('ALMA_IZ_KEY'),

        // SRU URL for institution zone
        'sru' => env('ALMA_IZ_SRU_URL'),

        // Base URL for institution zone. This only needs to be specified if you
        // use a proxy or other non-standard URL.
        'baseUrl' => 'https://gw-uio.intark.uh-it.no/alma/v1',

        // Optional list of extra headers to send with each request.
        'extraHeaders' => [
            'X-Gravitee-Api-Key' => env('ALMA_IZ_GRAVITEE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Network zone settings
    |--------------------------------------------------------------------------
    */
    'nz' => [
        // API key for institution zone
        'key' => env('ALMA_NZ_KEY'),

        // SRU URL for institution zone
        'sru' => env('ALMA_NZ_SRU_URL'),

        // Base URL for institution zone. This only needs to be specified if you
        // use a proxy or other non-standard URL.
        'baseUrl' => 'https://gw-uio.intark.uh-it.no/alma/v1',

        // Optional list of extra headers to send with each request.
        'extraHeaders' => [
            'X-Gravitee-Api-Key' => env('ALMA_NZ_GRAVITEE_KEY'),
        ],
    ],
];
