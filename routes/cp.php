<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\DashboardController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoDefaultsConfigurationController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionDefaultsConfigurationController;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/', DashboardController::class)->name('index');

    Route::get('/site', [SeoDefaultsController::class, 'index'])->name('site.index');
    Route::get('/site/{default}/{site}', [SeoDefaultsController::class, 'edit'])->name('site.defaults.edit');
    Route::patch('/site/{default}/{site}', [SeoDefaultsController::class, 'update'])->name('site.defaults.update');
    Route::get('/site/{default}/{site}/config', [SeoDefaultsConfigurationController::class, 'edit'])->name('site.config.edit');
    Route::patch('/site/{default}/{site}/config', [SeoDefaultsConfigurationController::class, 'update'])->name('site.config.update');

    Route::get('/collections', [CollectionDefaultsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}/{site}', [CollectionDefaultsController::class, 'edit'])->name('collections.defaults.edit');
    Route::patch('/collections/{collection}/{site}', [CollectionDefaultsController::class, 'update'])->name('collections.defaults.update');
    Route::get('/collections/{collection}/{site}/config', [CollectionDefaultsConfigurationController::class, 'edit'])->name('collections.config.edit');
    Route::patch('/collections/{collection}/{site}/config', [CollectionDefaultsConfigurationController::class, 'update'])->name('collections.config.update');

    Route::get('/taxonomies', [SeoDefaultsController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{taxonomy}/{site}', [SeoDefaultsController::class, 'edit'])->name('taxonomies.defaults.edit');
    Route::patch('/taxonomies/{taxonomy}/{site}', [SeoDefaultsController::class, 'update'])->name('taxonomies.defaults.update');
    Route::get('/taxonomies/{taxonomy}/{site}/config', [SeoDefaultsConfigurationController::class, 'edit'])->name('taxonomies.config.edit');
    Route::patch('/taxonomies/{taxonomy}/{site}/config', [SeoDefaultsConfigurationController::class, 'update'])->name('taxonomies.config.update');
});
