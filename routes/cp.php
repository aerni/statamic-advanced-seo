<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\GeneralController;
use Illuminate\Support\Facades\Route;

Route::get('advanced-seo/general', [GeneralController::class, 'index'])->name('advanced-seo.general.index');
Route::post('advanced-seo/general', [GeneralController::class, 'store'])->name('advanced-seo.general.store');
