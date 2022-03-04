<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disabled Collections & Taxonomies
    |--------------------------------------------------------------------------
    |
    | You may disable the SEO tab, the output of meta data,
    | and the generation of sitemaps for any collection and taxonomy
    | by adding its handle to the appropriate array below.
    |
    */

    'disabled' => [
        'collections' => [],
        'taxonomies' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Images
    |--------------------------------------------------------------------------
    |
    | Configure the social images feature to your liking.
    | If you want to use the generator, you need to install Puppeteer:
    | https://spatie.be/docs/browsershot/v2/requirements
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
    | Configure the favicons feature to your liking.
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
    | Configure the sitemap feature to your liking.
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
    | Configure the analytics trackers to your liking.
    | The trackers will only render in the environments defined below.
    | You may also disable any trackers you don't need.
    |
    */

    'analytics' => [
        'environments' => ['production'],
        'fathom' => true,
        'cloudflare_analytics' => true,
        'google_tag_manager' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Verification
    |--------------------------------------------------------------------------
    |
    | Configure the site verification feature to your liking.
    |
    */

    'site_verification' => true,

];
