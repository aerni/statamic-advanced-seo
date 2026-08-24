<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Driver (Requires Pro)
    |--------------------------------------------------------------------------
    |
    | Choose the driver for storing data. This can either be 'file' or 'eloquent'.
    | The 'eloquent' driver requires Pro.
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Store Directory
    |--------------------------------------------------------------------------
    |
    | The directory in which Advanced SEO saves its data when using the file driver.
    |
    */

    'directory' => base_path('content/seo'),

    /*
    |--------------------------------------------------------------------------
    | GraphQL (Requires Pro)
    |--------------------------------------------------------------------------
    |
    | You may enable the GraphQL API for this addon.
    |
    */

    'graphql' => false,

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
            'twitter_summary_large_image' => ['width' => 1200, 'height' => 630],
        ],

        /*
        |--------------------------------------------------------------------------
        | Social Images Generator (Requires Pro)
        |--------------------------------------------------------------------------
        |
        | Requires spatie/laravel-screenshot to be installed.
        | It supports Browsershot and Cloudflare as screenshot drivers.
        | Read the documentation for setup and configuration:
        | https://spatie.be/docs/laravel-screenshot
        |
        */

        'generator' => [

            /*
            |--------------------------------------------------------------------------
            | Enabled
            |--------------------------------------------------------------------------
            |
            | Enable or disable the social images generator.
            |
            */

            'enabled' => false,

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

        'environments' => ['local', 'production'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap (Requires Pro)
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

        'enabled' => false,

        /*
        |--------------------------------------------------------------------------
        | Storage Path
        |--------------------------------------------------------------------------
        |
        | The directory where your sitemap files will be located.
        |
        */

        'path' => storage_path('statamic/sitemaps'),

        /*
        |--------------------------------------------------------------------------
        | Queue
        |--------------------------------------------------------------------------
        |
        | The queue that is used when generating the sitemaps.
        |
        */

        'queue' => 'default',

        /*
        |--------------------------------------------------------------------------
        | Custom Sitemaps
        |--------------------------------------------------------------------------
        |
        | Register custom sitemap classes. Each class should extend
        | CustomSitemap and define a handle and urls.
        | The site defaults to the default Statamic site if not set.
        |
        */

        'custom' => [
            //
        ],

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

    /*
    |--------------------------------------------------------------------------
    | Tokens (Requires Pro)
    |--------------------------------------------------------------------------
    |
    | Register custom token normalizers for fieldtypes not covered by
    | the defaults, or add custom value tokens. Both are available
    | in the token input fieldtype.
    |
    */

    'tokens' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | AI (Requires Pro)
    |--------------------------------------------------------------------------
    |
    | Configure AI-powered content generation.
    | Requires the Laravel AI SDK (laravel/ai) to be installed and configured.
    |
    */

    'ai' => [

        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Enable or disable AI content generation.
        |
        */

        'enabled' => false,

        /*
        |--------------------------------------------------------------------------
        | Provider
        |--------------------------------------------------------------------------
        |
        | The AI provider to use for content generation. This should match
        | a provider configured in your config/ai.php file. When set to null,
        | the SDK's default provider will be used.
        |
        */

        'provider' => null,

        /*
        |--------------------------------------------------------------------------
        | Model
        |--------------------------------------------------------------------------
        |
        | The AI model to use for content generation. When set to null,
        | the provider's cheapest model will be used for cost optimization.
        |
        */

        'model' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects (Requires Pro)
    |--------------------------------------------------------------------------
    |
    | Configure the redirects feature to your liking.
    |
    */

    'redirects' => [

        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Disable to turn off redirect handling and 404 tracking entirely.
        |
        */

        'enabled' => false,

        /*
        |--------------------------------------------------------------------------
        | Store Directory
        |--------------------------------------------------------------------------
        |
        | The directory where redirects are stored when using the file driver.
        |
        */

        'directory' => base_path('content/redirects'),

        /*
        |--------------------------------------------------------------------------
        | Hits
        |--------------------------------------------------------------------------
        |
        | Track how many times each redirect is used and when it was last hit.
        |
        */

        'hits' => [

            /*
            |--------------------------------------------------------------------------
            | Enabled
            |--------------------------------------------------------------------------
            |
            | Disable to stop recording redirect hits.
            |
            */

            'enabled' => true,

            /*
            |--------------------------------------------------------------------------
            | Store Directory
            |--------------------------------------------------------------------------
            |
            | The directory where hit records are stored when using the file driver.
            |
            */

            'directory' => storage_path('statamic/advanced-seo/redirect-hits'),

        ],

        /*
        |--------------------------------------------------------------------------
        | Errors
        |--------------------------------------------------------------------------
        |
        | Log 404s that no redirect handles so you can turn them into redirects.
        |
        */

        'errors' => [

            /*
            |--------------------------------------------------------------------------
            | Enabled
            |--------------------------------------------------------------------------
            |
            | Disable to stop recording unhandled 404s.
            |
            */

            'enabled' => true,

            /*
            |--------------------------------------------------------------------------
            | Store Directory
            |--------------------------------------------------------------------------
            |
            | The directory where error records are stored when using the file driver.
            |
            */

            'directory' => storage_path('statamic/advanced-seo/redirect-errors'),

            /*
            |--------------------------------------------------------------------------
            | Retention
            |--------------------------------------------------------------------------
            |
            | Errors not seen within this many days are removed by the daily
            | prune command (seo:prune-redirect-errors). Set to an integer, or
            | false to disable age-based pruning.
            |
            */

            'purge_after_days' => 30,

            /*
            |--------------------------------------------------------------------------
            | Record Cap
            |--------------------------------------------------------------------------
            |
            | The list keeps the most-hit errors up to this cap, evicting the
            | lowest-count records first. Set to an integer, or false to disable
            | the cap for both drivers. Disabling the cap is only recommended with
            | Eloquent because the file/Stache driver adds a YAML file for every
            | recorded 404. The cap is best-effort under concurrent recording and
            | may be briefly exceeded until the next write or prune.
            |
            */

            'max_records' => 1000,

            /*
            |--------------------------------------------------------------------------
            | Ignored Paths
            |--------------------------------------------------------------------------
            |
            | Paths matching any of these patterns are never recorded as errors,
            | keeping bot and scanner noise out of the list. Use an exact path
            | (/foo), a wildcard (/bar/*), or a regex (#...#), just like a
            | redirect source. Keep regex patterns simple, they run against
            | request paths on every unmatched 404.
            |
            */

            'ignore' => [
                '#\.php$#',                              // PHP probes: wp-login.php, xmlrpc.php, admin-ajax.php
                '#/wp-(admin|includes|content)(/|$)#',   // WordPress probes at any depth
                '#^/\.(env|git)#',                       // Secrets and repo probes
            ],

        ],

        /*
        |--------------------------------------------------------------------------
        | Queue
        |--------------------------------------------------------------------------
        |
        | The queue used for the background hit and error recording jobs.
        |
        */

        'queue' => 'default',

    ],

];
