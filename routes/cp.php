<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\DashboardController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetIndexController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetLocalizationController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetStateController;
use Illuminate\Support\Facades\Route;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/{seoSetGroup}', SeoSetIndexController::class)->name('sets.index');
    Route::get('/{seoSetGroup}/{seoSet}/config', [SeoSetConfigController::class, 'edit'])->name('sets.config');
    Route::patch('/{seoSetGroup}/{seoSet}/config', [SeoSetConfigController::class, 'update'])->name('sets.config');
    Route::post('/{seoSetGroup}/{seoSet}/enable', [SeoSetStateController::class, 'enable'])->name('sets.enable');
    Route::post('/{seoSetGroup}/{seoSet}/disable', [SeoSetStateController::class, 'disable'])->name('sets.disable');
    Route::get('/{seoSetGroup}/{seoSet}/{seoSetLocalization}', [SeoSetLocalizationController::class, 'edit'])->name('sets.localization');
    Route::patch('/{seoSetGroup}/{seoSet}/{seoSetLocalization}', [SeoSetLocalizationController::class, 'update'])->name('sets.localization');
});
