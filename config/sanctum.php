<?php

use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\PersonalAccessToken;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => [],

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be used when
    | authenticating users. This application uses "session" based guard since
    | this is a stateful API. However, you are free to customize it.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will
    | be considered expired. If this value is null, personal access tokens
    | will never expire. This will override any values set in token lifetime
    | configurations here.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to facilitate identification
    | on the server. If this value is null, no prefix will be used.
    |
    */

    'prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Personal Access Token Model
    |--------------------------------------------------------------------------
    |
    | This model is used when creating and authenticating personal access
    | tokens. The default model is included in Laravel, but you may
    | replace it with a custom model if needed.
    |
    */

    'personal_access_tokens' => PersonalAccessToken::class,

];
