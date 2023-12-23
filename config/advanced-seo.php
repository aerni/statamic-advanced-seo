<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Directory
    |--------------------------------------------------------------------------
    |
    | You may customize the directory in which Advanced SEO saves its data.
    |
    */

    'directory' => base_path('content/seo'),

    /*
    |--------------------------------------------------------------------------
    | GraphQL
    |--------------------------------------------------------------------------
    |
    | You may enable the GraphQL API for this addon.
    |
    */

    'graphql' => false,

    /*
    |--------------------------------------------------------------------------
    | Disabled Collections & Taxonomies
    |--------------------------------------------------------------------------
    |
    | Disable Advanced SEO for any collection and taxonomy by adding
    | its handle to the appropriate array below. This will remove the SEO tab,
    | stop the output of meta data on the frontend, and remove the sitemap.
    |
    */

    'disabled' => [
        'collections' => [],
        'taxonomies' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Composer
    |--------------------------------------------------------------------------
    |
    | Configure the views that the Advanced SEO view composer should apply to.
    | The view composer is responsible for making the SEO data available in your views.
    | By default, this is set to '*' to apply to all views, but you can change
    | this to an array of specific views to limit the view composer's application.
    |
    */

    'view_composer' => '*',

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
            'open_graph' => ['width' => 1200, 'height' => 630],
            'twitter_summary' => ['width' => 240, 'height' => 240],
            'twitter_summary_large_image' => ['width' => 1260, 'height' => 630],
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
            | Enable or disable the generator for all collections.
            |
            */

            'enabled' => false,

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
    | Crawling
    |--------------------------------------------------------------------------
    |
    | Configure the crawling feature to your liking.
    |
    */

    'crawling' => [

        /*
        |--------------------------------------------------------------------------
        | Environments
        |--------------------------------------------------------------------------
        |
        | Configure the environments in which you want the sites to be crawled.
        | The robots meta tag will be set to `noindex, nofollow` in all other environments.
        |
        */

        'environments' => ['production'],

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
        | The sitemap cache expiry in minutes.
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
        | The tracker scripts will only render in the configured environments.
        |
        */

        'environments' => ['production'],

        /*
        |--------------------------------------------------------------------------
        | Enabled Trackers
        |--------------------------------------------------------------------------
        |
        | Disable the trackers you don't need. This will remove the tracker's
        | section from the analytics settings in the control panel.
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
    | Disabling this feature will remove the site verification section
    | from the settings in the control panel.
    |
    */

    'site_verification' => true,

];
