<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Favicons
    |--------------------------------------------------------------------------
    |
    | Do you want to enable the Favicon settings?
    |
    */

    'favicons' => true,

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    |
    | Choose to enable or disable the Sitemap functionality
    | and configure the cache expiry in minutes.
    |
    */

    'sitemap' => [
        'enabled' => true,
        'expiry' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Images
    |--------------------------------------------------------------------------
    |
    | Configure the options for your social images.
    |
    */

    'social_images' => [

        /*
        |--------------------------------------------------------------------------
        | Assets Container
        |--------------------------------------------------------------------------
        |
        | Specify the container you want to use for your social images.
        |
        */

        'container' => 'assets',

        /*
        |--------------------------------------------------------------------------
        | Social Images Generator
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Social Images Generator?
        | This requires Puppeteer and Browsershot.
        |
        */

        'generator' => [
            'enabled' => false,
            'queue' => 'default',
        ],

        /*
        |--------------------------------------------------------------------------
        | Open Graph Images
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Open Graph Images settings?
        |
        */

        'open_graph' => true,

        /*
        |--------------------------------------------------------------------------
        | Twitter Images
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Twitter Images settings?
        |
        */

        'twitter' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Trackers
    |--------------------------------------------------------------------------
    |
    | Configure the options for your trackers
    |
    */

    'trackers' => [

        /*
        |--------------------------------------------------------------------------
        | Site Verification
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Site Verification settings?
        |
        */

        'site_verification' => true,

        /*
        |--------------------------------------------------------------------------
        | Fathom Analytics
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Fathom Analytics settings?
        |
        */

        'fathom' => true,

        /*
        |--------------------------------------------------------------------------
        | Cloudflare Analytics
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Cloudflare Analytics settings?
        |
        */

        'cloudflare_analytics' => true,

        /*
        |--------------------------------------------------------------------------
        | Google Tag Manager
        |--------------------------------------------------------------------------
        |
        | Do you want to enable the Google Tag Manager settings?
        |
        */

        'google_tag_manager' => true,

    ],

];
