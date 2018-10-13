<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primo configuration
    |--------------------------------------------------------------------------
    */
    'host' => env('PRIMO_HOST'),
    'baseUrl' => env('PRIMO_BASE_URL'),
    'searchUrl' => env('PRIMO_SEARCH_URL'),
    'vid' => env('PRIMO_VID'),
    'inst' => env('PRIMO_INST'),
    'scope' => env('PRIMO_SCOPE'),
    'indices' => env('PRIMO_INDICES', [
        'ddc' => '10',
        'tekord' => '12',
        'udc' => '13',
        'humord' => '14',
        'nlm' => '15',
        'agrovoc' => '16',
        'geo' => '17',
        'ubo' => '18', 'msc' => '18',
        'mrtermer' => '19',
        'realfagstermer' => '20',
        'avdeling' => '41',
        'avdelingsamling' => '51',
    ]),

];
