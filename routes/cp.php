<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomyDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\OverviewController;

// TODO: Use route group.

Route::get('advanced-seo', [OverviewController::class, 'index'])->name('advanced-seo.index');
Route::get('advanced-seo/{group}', [OverviewController::class, 'show'])->name('advanced-seo.show');

Route::get('advanced-seo/site/{default}', [SiteDefaultsController::class, 'edit'])->name('advanced-seo.site.edit');
Route::patch('advanced-seo/site/{default}', [SiteDefaultsController::class, 'update'])->name('advanced-seo.site.update');

Route::get('advanced-seo/content/collections/{collection}', [CollectionDefaultsController::class, 'edit'])->name('advanced-seo.content.collections.edit');
Route::patch('advanced-seo/content/collections/{collection}', [CollectionDefaultsController::class, 'update'])->name('advanced-seo.content.collections.update');

Route::get('advanced-seo/content/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'edit'])->name('advanced-seo.content.taxonomies.edit');
Route::patch('advanced-seo/content/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'update'])->name('advanced-seo.content.taxonomies.update');
