<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primo configuration
    |--------------------------------------------------------------------------
    */

    'primo' => [
        'host' => env('PRIMO_HOST', 'bibsys-primo.hosted.exlibrisgroup.com'),  // bibsys-almaprimo.hosted.exlibrisgroup.com'), // bibsys-primo.hosted.exlibrisgroup.com'),
        'institution' => env('PRIMO_INST', 'UBO'),
        'default_scope' => 'BIBSYS_ILS',
        'indices' => env('PRIMO_INDICES', [
            'dewey' => '10',
            'tekord' => '12',
            'humord' => '14',
            'stedsnavn' => '17',
            'menneskerettighstermer' => '19',
            'realfagstermer' => '20',
            'avdeling' => '41',
            'avdelingsamling' => '51',
        ]),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alma configuration
    |--------------------------------------------------------------------------
    */

    'alma' => [
        'host' => env('ALMA_HOST', 'bibsys-k.alma.exlibrisgroup.com'),
        'institution' => env('ALMA_INST', '47BIBSYS_UBO'),
    ],
];
