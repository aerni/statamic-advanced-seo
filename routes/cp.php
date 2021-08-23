<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SiteDefaultsController;

Route::view('advanced-seo', 'advanced-seo::cp.index')->name('advanced-seo.index');

Route::get('advanced-seo/site', [SiteDefaultsController::class, 'edit'])->name('advanced-seo.site.edit');
Route::post('advanced-seo/site', [SiteDefaultsController::class, 'update'])->name('advanced-seo.site.update');

Route::view('advanced-seo/content', 'advanced-seo::content')->name('advanced-seo.content.index');
Route::get('advanced-seo/content/collections/{collection}/edit', 'CollectionDefaultsController@edit')->name('advanced-seo.content.collections.edit');
Route::post('advanced-seo/content/collections/{collection}', 'CollectionDefaultsController@update')->name('advanced-seo.content.collections.update');
Route::get('advanced-seo/content/taxonomies/{taxonomy}/edit', 'TaxonomyDefaultsController@edit')->name('advanced-seo.content.taxonomies.edit');
Route::post('advanced-seo/content/taxonomies/{taxonomy}', 'TaxonomyDefaultsController@update')->name('advanced-seo.content.taxonomies.update');
