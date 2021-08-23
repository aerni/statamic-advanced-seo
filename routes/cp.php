<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\CollectionDefaultsController;
use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteDefaultsController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\TaxonomyDefaultsController;

Route::view('advanced-seo', 'advanced-seo::cp.index')->name('advanced-seo.index');

Route::get('advanced-seo/site', [SiteDefaultsController::class, 'edit'])->name('advanced-seo.site.edit');
Route::post('advanced-seo/site', [SiteDefaultsController::class, 'update'])->name('advanced-seo.site.update');

Route::view('advanced-seo/content', 'advanced-seo::cp.content')->name('advanced-seo.content.index');
Route::get('advanced-seo/content/collections/{collection}', [CollectionDefaultsController::class, 'edit'])->name('advanced-seo.content.collections.edit');
Route::post('advanced-seo/content/collections/{collection}', [CollectionDefaultsController::class, 'update'])->name('advanced-seo.content.collections.update');
Route::get('advanced-seo/content/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'edit'])->name('advanced-seo.content.taxonomies.edit');
Route::post('advanced-seo/content/taxonomies/{taxonomy}', [TaxonomyDefaultsController::class, 'update'])->name('advanced-seo.content.taxonomies.update');
