<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoDefaultsController;
use Illuminate\Support\Facades\Route;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/site', [SeoDefaultsController::class, 'index'])->name('site.index');
    Route::get('/site/{default}', [SeoDefaultsController::class, 'edit'])->name('site.edit');
    Route::patch('/site/{default}', [SeoDefaultsController::class, 'update'])->name('site.update');

    Route::get('/collections', [SeoDefaultsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}', [SeoDefaultsController::class, 'edit'])->name('collections.edit');
    Route::patch('/collections/{collection}', [SeoDefaultsController::class, 'update'])->name('collections.update');

    Route::get('/taxonomies', [SeoDefaultsController::class, 'index'])->name('taxonomies.index');
    Route::get('/taxonomies/{taxonomy}', [SeoDefaultsController::class, 'edit'])->name('taxonomies.edit');
    Route::patch('/taxonomies/{taxonomy}', [SeoDefaultsController::class, 'update'])->name('taxonomies.update');
});
