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
    |
    */

    'social_images' => [

        /*
        |--------------------------------------------------------------------------
        | Asset Container
        |--------------------------------------------------------------------------
        |
        | The asset container that will be used for your social images.
        |
        */

        'container' => 'assets',

        /*
        |--------------------------------------------------------------------------
        | Presets
        |--------------------------------------------------------------------------
        |
        | The presets defining the width and height of your social images.
        |
        */

        'presets' => [
            'open_graph' => ['width' => 1200, 'height' => 628],
            'twitter_summary' => ['width' => 240, 'height' => 240],
            'twitter_summary_large_image' => ['width' => 1100, 'height' => 628],
        ],

        /*
        |--------------------------------------------------------------------------
        | Social Images Generator
        |--------------------------------------------------------------------------
        |
        | To use the social images generator, you need to install Puppeteer:
        | https://spatie.be/docs/browsershot/v2/requirements
        |
        */

        'generator' => [

            /*
            |--------------------------------------------------------------------------
            | Enabled
            |--------------------------------------------------------------------------
            |
            | You may enable or disable the generator for you whole project.
            |
            */

            'enabled' => true,

            /*
            |--------------------------------------------------------------------------
            | Generate on Save
            |--------------------------------------------------------------------------
            |
            | Generate the social images every time an entry is saved.
            | Disable this to generate the image the first time an entry is
            | viewed on the frontend instead.
            |
            */

            'generate_on_save' => true,

            /*
            |--------------------------------------------------------------------------
            | Queue
            |--------------------------------------------------------------------------
            |
            | The queue that is used when generating the social images.
            |
            */

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

        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Disable the feature if you want to manually add the favicons yourself.
        |
        */

        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Asset Container
        |--------------------------------------------------------------------------
        |
        | The asset container that will be used for your favicons.
        |
        */

        'container' => 'assets',

    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    |
    | Configure the sitemap feature to your liking.
    |
    */

    'sitemap' => [

        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Disable the feature if you want to implement your own sitemaps.
        |
        */

        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Cache Expiry
        |--------------------------------------------------------------------------
        |
        | The time in minutes the sitemap will be cached for.
        |
        */

        'expiry' => 60,

    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Trackers
    |--------------------------------------------------------------------------
    |
    | Configure the analytics trackers to your liking.
    |
    */

    'analytics' => [

        /*
        |--------------------------------------------------------------------------
        | Environments
        |--------------------------------------------------------------------------
        |
        | The tracker scripts will only render in the defined environments.
        |
        */

        'environments' => ['production'],

        /*
        |--------------------------------------------------------------------------
        | Enabled Trackers
        |--------------------------------------------------------------------------
        |
        | Disable the trackers you don't need for the project.
        |
        */

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
