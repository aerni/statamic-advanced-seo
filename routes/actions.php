<?php

use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;
use Illuminate\Support\Facades\Route;

Route::name('advanced-seo.')->group(function () {
    Route::get('/social-images/{theme}/{template}/{id}', SocialImagesController::class)->name('social-images');
});
