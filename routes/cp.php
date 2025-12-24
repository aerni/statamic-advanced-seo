<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionsConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\DashboardController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomiesConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomiesController;
use Illuminate\Support\Facades\Route;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/', DashboardController::class)->name('index');

    Route::get('/site', [SiteController::class, 'index'])->name('site.index');
    Route::get('/site/{default}/edit', [SiteConfigController::class, 'edit'])->name('site.edit');
    Route::patch('/site/{default}/edit', [SiteConfigController::class, 'update'])->name('site.edit');
    Route::get('/site/{default}/{site}', [SiteController::class, 'edit'])->name('site.defaults');
    Route::patch('/site/{default}/{site}', [SiteController::class, 'update'])->name('site.defaults');

    Route::get('/collections', [CollectionsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}/edit', [CollectionsConfigController::class, 'edit'])->name('collections.edit');
    Route::patch('/collections/{collection}/edit', [CollectionsConfigController::class, 'update'])->name('collections.edit');
    Route::get('/collections/{collection}/{site}', [CollectionsController::class, 'edit'])->name('collections.defaults');
    Route::patch('/collections/{collection}/{site}', [CollectionsController::class, 'update'])->name('collections.defaults');

    Route::get('/taxonomies', [TaxonomiesController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{taxonomy}/edit', [TaxonomiesConfigController::class, 'edit'])->name('taxonomies.edit');
    Route::patch('/taxonomies/{taxonomy}/edit', [TaxonomiesConfigController::class, 'update'])->name('taxonomies.edit');
    Route::get('/taxonomies/{taxonomy}/{site}', [TaxonomiesController::class, 'edit'])->name('taxonomies.defaults');
    Route::patch('/taxonomies/{taxonomy}/{site}', [TaxonomiesController::class, 'update'])->name('taxonomies.defaults');
});
