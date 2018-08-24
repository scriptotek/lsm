<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primo configuration
    |--------------------------------------------------------------------------
    */

    'primo' => [
        'host' => 'https://bibsys-almaprimo.hosted.exlibrisgroup.com',
        'default_institution' => 'UBO',
        'default_scope' => 'BIBSYS_ILS',
        'institutions' => [
            'UBO' => [
                'institution' => 'UBO',
                'view' => 'UIO',
                'default_scope' => 'BIBSYS_ILS',
            ],
            'UBB' => [
                'institution' => 'UBB',
                'view' => 'UBB',
                'default_scope' => 'BIBSYS_ILS',
            ],
        ],
        'indices' => [
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
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alma configuration
    |--------------------------------------------------------------------------
    */

    'alma' => [
        'default_institution' => 'UBO',
    ]

];
