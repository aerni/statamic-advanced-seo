<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\DashboardController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomiesController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionsConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomiesConfigController;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/', DashboardController::class)->name('index');

    Route::get('/site', [SiteController::class, 'index'])->name('site.index');
    Route::get('/site/{default}/{site}', [SiteController::class, 'edit'])->name('site.defaults');
    Route::patch('/site/{default}/{site}', [SiteController::class, 'update'])->name('site.defaults');
    Route::get('/site/{default}/{site}/config', [SiteConfigController::class, 'edit'])->name('site.config');
    Route::patch('/site/{default}/{site}/config', [SiteConfigController::class, 'update'])->name('site.config');

    Route::get('/collections', [CollectionsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}/{site}', [CollectionsController::class, 'edit'])->name('collections.defaults');
    Route::patch('/collections/{collection}/{site}', [CollectionsController::class, 'update'])->name('collections.defaults');
    Route::get('/collections/{collection}/{site}/config', [CollectionsConfigController::class, 'edit'])->name('collections.config');
    Route::patch('/collections/{collection}/{site}/config', [CollectionsConfigController::class, 'update'])->name('collections.config');

    Route::get('/taxonomies', [TaxonomiesController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{taxonomy}/{site}', [TaxonomiesController::class, 'edit'])->name('taxonomies.defaults');
    Route::patch('/taxonomies/{taxonomy}/{site}', [TaxonomiesController::class, 'update'])->name('taxonomies.defaults');
    Route::get('/taxonomies/{taxonomy}/{site}/config', [TaxonomiesConfigController::class, 'edit'])->name('taxonomies.config');
    Route::patch('/taxonomies/{taxonomy}/{site}/config', [TaxonomiesConfigController::class, 'update'])->name('taxonomies.config');
});
