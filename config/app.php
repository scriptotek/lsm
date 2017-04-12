<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primo configuration
    |--------------------------------------------------------------------------
    */

    'primo' => [
        'host' => env('PRIMO_HOST', 'https://bibsys-almaprimo.hosted.exlibrisgroup.com'),  // bibsys-almaprimo.hosted.exlibrisgroup.com'), // bibsys-primo.hosted.exlibrisgroup.com'),
        'institution' => env('PRIMO_INST', 'UBO'),
        'default_scope' => 'BIBSYS_ILS',
        'indices' => env('PRIMO_INDICES', [
            'dewey' => '10',
            'tekord' => '12',
            'humord' => '14',
            'geo' => '17',
            'mrtermer' => '19',
            'realfagstermer' => '20',
            'avdeling' => '41',
            'avdelingsamling' => '51',
        ]),
    ],

];
