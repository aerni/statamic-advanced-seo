<?php

use Illuminate\Support\Facades\Route;

Route::statamic('/seo/social-images/og/{id}', 'social_images/og', [
    'layout' => 'social_images/layout',
]);

Route::statamic('/seo/social-images/twitter/{id}', 'social_images/twitter', [
    'layout' => 'social_images/layout',
]);
