<?php

return [
    'dsn' => env('SENTRY_DSN'),

    // capture release as git sha
    'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),

    // Capture bindings on SQL queries
    'breadcrumbs' => [
        // Capture bindings on SQL queries logged in breadcrumbs
        'sql_bindings' => true,
    ],
];
