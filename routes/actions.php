<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\ConditionsController;
use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;
use Illuminate\Support\Facades\Route;

Route::name('advanced-seo.')->group(function () {
    Route::get('/social-images/{theme}/{type}/{id}', [SocialImagesController::class, 'show'])->name('social_images.show');
    Route::get('/conditions/{id}', ConditionsController::class);
});
