<?php
return [
    /*
    |--------------------------------------------------------------------------
    | CDNify CDN list.
    |--------------------------------------------------------------------------
    |
    | This is a list of CDN's for cdnify to use, it will most likely be a url.
    |
    */
    'cdn' => [
        'cloudfront' => 'https://' . env('AWS_CLOUDFRONT', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Elixir.
    |--------------------------------------------------------------------------
    |
    | This specifies if cdnify should use elixir() to wrap the path.
    |
    */
    'elixir' => true,

    /*
    |--------------------------------------------------------------------------
    | Environment.
    |--------------------------------------------------------------------------
    |
    | This specifies which environments require a cdn path prefixing.
    |
    */
    'environments' => [
        'staging',
        'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Round robin.
    |--------------------------------------------------------------------------
    |
    | This will rotate through the list of provided CDNs each time a call
    | to cdn() is made.
    |
    */
    'round_robin' => false,
];