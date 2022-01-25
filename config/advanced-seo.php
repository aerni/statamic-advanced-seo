<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Excluded Collections & Taxonomies
    |--------------------------------------------------------------------------
    |
    | You may disable the SEO tab and SEO data generation for any collection
    | and taxonomy by adding its handle to the appropriate array below.
    |
    */

    'excluded_collections' => [],
    'excluded_taxonomies' => [],

    /*
    |--------------------------------------------------------------------------
    | Social Images
    |--------------------------------------------------------------------------
    |
    | Configure the Social Images feature to your liking.
    | The generator requires Puppeteer: https://github.com/spatie/browsershot#requirements
    |
    */

    'social_images' => [

        'container' => 'assets',

        'presets' => [
            'open_graph' => ['width' => 1200, 'height' => 628],
            'twitter' => [
                'summary' => ['width' => 240, 'height' => 240],
                'summary_large_image' => ['width' => 1100, 'height' => 628],
            ],
        ],

        'generator' => [
            'enabled' => true,
            'queue' => 'default',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Favicons
    |--------------------------------------------------------------------------
    |
    | Configure the Favicons feature to your liking.
    |
    */

    'favicons' => [
        'enabled' => true,
        'container' => 'assets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    |
    | Configure the Sitemap feature to your liking.
    | The default cache expiry is 60 minutes.
    |
    */

    'sitemap' => [
        'enabled' => true,
        'expiry' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Trackers
    |--------------------------------------------------------------------------
    |
    | Choose to enable/disable specific analytics trackers.
    |
    */

    'analytics' => [
        'fathom' => true,
        'cloudflare_analytics' => true,
        'google_tag_manager' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Verification
    |--------------------------------------------------------------------------
    |
    | Choose to enable/disable the Site Verification feature.
    |
    */

    'site_verification' => true,

];
