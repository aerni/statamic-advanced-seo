<?php

use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;
use Illuminate\Support\Facades\Route;

Route::get('/social-images/{theme}/{template}/{id}', SocialImagesController::class);
