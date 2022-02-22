<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomyDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionDefaultsController;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/site', [SiteDefaultsController::class, 'index'])->name('site.index');
    Route::get('/site/{default}', [SiteDefaultsController::class, 'edit'])->name('site.edit');
    Route::patch('/site/{default}', [SiteDefaultsController::class, 'update'])->name('site.update');

    Route::get('/collections', [CollectionDefaultsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}', [CollectionDefaultsController::class, 'edit'])->name('collections.edit');
    Route::patch('/collections/{collection}', [CollectionDefaultsController::class, 'update'])->name('collections.update');

    Route::get('/taxonomies', [TaxonomyDefaultsController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'edit'])->name('taxonomies.edit');
    Route::patch('/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'update'])->name('taxonomies.update');
});
