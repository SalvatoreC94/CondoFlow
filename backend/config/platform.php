<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform operator seed account
    |--------------------------------------------------------------------------
    |
    | Credentials for the initial /platform login, created by
    | PlatformUserSeeder. Override these in .env for every real
    | environment — the defaults below are for local development only.
    |
    */

    'operator' => [
        'name' => env('PLATFORM_OPERATOR_NAME', 'CondoFlow Operator'),
        'email' => env('PLATFORM_OPERATOR_EMAIL', 'operator@condoflow.test'),
        'password' => env('PLATFORM_OPERATOR_PASSWORD', 'password'),
    ],

];
