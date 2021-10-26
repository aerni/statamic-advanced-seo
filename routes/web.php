<?php

use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;

Site::all()->each(function ($site) {
    Route::statamic("{$site->url()}/seo/social-images/og/{id}", 'social_images/og', [
        'layout' => 'social_images/layout',
    ]);

    Route::statamic("{$site->url()}/seo/social-images/twitter/{id}", 'social_images/twitter', [
        'layout' => 'social_images/layout',
    ]);
});
