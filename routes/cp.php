<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoDefaultsConfigurationController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoDefaultsController;
use Illuminate\Support\Facades\Route;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/site', [SeoDefaultsController::class, 'index'])->name('site.index');
    Route::get('/site/{default}', [SeoDefaultsController::class, 'edit'])->name('site.edit');
    Route::patch('/site/{default}', [SeoDefaultsController::class, 'update'])->name('site.update');
    Route::get('/site/{default}/configure', [SeoDefaultsConfigurationController::class, 'edit'])->name('site.configure.edit');
    Route::patch('/site/{default}/configure', [SeoDefaultsConfigurationController::class, 'update'])->name('site.configure.update');

    Route::get('/collections', [SeoDefaultsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}', [SeoDefaultsController::class, 'edit'])->name('collections.edit');
    Route::patch('/collections/{collection}', [SeoDefaultsController::class, 'update'])->name('collections.update');
    Route::get('/collections/{collection}/configure', [SeoDefaultsConfigurationController::class, 'edit'])->name('collections.configure.edit');
    Route::patch('/collections/{collection}/configure', [SeoDefaultsConfigurationController::class, 'update'])->name('collections.configure.update');

    Route::get('/taxonomies', [SeoDefaultsController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{taxonomy}', [SeoDefaultsController::class, 'edit'])->name('taxonomies.edit');
    Route::patch('/taxonomies/{taxonomy}', [SeoDefaultsController::class, 'update'])->name('taxonomies.update');
    Route::get('/taxonomies/{taxonomy}/configure', [SeoDefaultsConfigurationController::class, 'edit'])->name('taxonomies.configure.edit');
    Route::patch('/taxonomies/{taxonomy}/configure', [SeoDefaultsConfigurationController::class, 'update'])->name('taxonomies.configure.update');
});
