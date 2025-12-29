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
    Route::get('/site/{seoSet}/edit', [SiteConfigController::class, 'edit'])->name('site.edit');
    Route::patch('/site/{seoSet}/edit', [SiteConfigController::class, 'update'])->name('site.edit');
    Route::get('/site/{seoSetLocalization}/{site}', [SiteController::class, 'edit'])->name('site.localization');
    Route::patch('/site/{seoSetLocalization}/{site}', [SiteController::class, 'update'])->name('site.localization');

    Route::get('/collections', [CollectionsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{seoSet}/edit', [CollectionsConfigController::class, 'edit'])->name('collections.edit');
    Route::patch('/collections/{seoSet}/edit', [CollectionsConfigController::class, 'update'])->name('collections.edit');
    Route::get('/collections/{seoSetLocalization}/{site}', [CollectionsController::class, 'edit'])->name('collections.localization');
    Route::patch('/collections/{seoSetLocalization}/{site}', [CollectionsController::class, 'update'])->name('collections.localization');


    Route::get('/taxonomies', [TaxonomiesController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{seoSet}/edit', [TaxonomiesConfigController::class, 'edit'])->name('taxonomies.edit');
    Route::patch('/taxonomies/{seoSet}/edit', [TaxonomiesConfigController::class, 'update'])->name('taxonomies.edit');
    Route::get('/taxonomies/{seoSetLocalization}/{site}', [TaxonomiesController::class, 'edit'])->name('taxonomies.localization');
    Route::patch('/taxonomies/{seoSetLocalization}/{site}', [TaxonomiesController::class, 'update'])->name('taxonomies.localization');
});
