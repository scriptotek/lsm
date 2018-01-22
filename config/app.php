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
    ],

];
