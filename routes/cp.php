<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomyDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\OverviewController;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/', [OverviewController::class, 'index'])->name('index');
    Route::get('/{group}', [OverviewController::class, 'show'])->name('show');

    Route::get('/site/{default}', [SiteDefaultsController::class, 'edit'])->name('site.edit');
    Route::patch('/site/{default}', [SiteDefaultsController::class, 'update'])->name('site.update');

    Route::get('/content/collections/{collection}', [CollectionDefaultsController::class, 'edit'])->name('content.collections.edit');
    Route::patch('/content/collections/{collection}', [CollectionDefaultsController::class, 'update'])->name('content.collections.update');

    Route::get('/content/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'edit'])->name('content.taxonomies.edit');
    Route::patch('/content/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'update'])->name('content.taxonomies.update');
});
