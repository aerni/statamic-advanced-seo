<?php

use Aerni\AdvancedSeo\Http\Controllers\Cp\AiGenerateController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\DashboardController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\RedirectActionController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\RedirectController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\RedirectErrorActionController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\RedirectErrorController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\RedirectExportController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\RedirectImportController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetConfigController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetIndexController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetLocalizationController;
use Aerni\AdvancedSeo\Http\Controllers\Cp\SeoSetStateController;
use Illuminate\Support\Facades\Route;

Route::prefix('advanced-seo')->name('advanced-seo.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('/ai/generate', AiGenerateController::class)->name('ai.generate');

    Route::get('/errors', [RedirectErrorController::class, 'index'])->name('redirects.errors.index');
    Route::post('/errors/clear', [RedirectErrorController::class, 'clear'])->name('redirects.errors.clear');
    Route::post('/errors/actions', [RedirectErrorActionController::class, 'run'])->name('redirects.errors.actions.run');
    Route::post('/errors/actions/list', [RedirectErrorActionController::class, 'bulkActions'])->name('redirects.errors.actions.bulk');

    Route::post('/redirects/import', RedirectImportController::class)->name('redirects.import');
    Route::get('/redirects/export/{format}', RedirectExportController::class)->name('redirects.export')->where('format', 'csv|json');
    Route::post('/redirects/actions', [RedirectActionController::class, 'run'])->name('redirects.actions.run');
    Route::post('/redirects/actions/list', [RedirectActionController::class, 'bulkActions'])->name('redirects.actions.bulk');
    Route::resource('redirects', RedirectController::class)->parameters(['redirects' => 'seoRedirect'])->except('show');

    Route::get('/{seoSetGroup}', SeoSetIndexController::class)->name('sets.index');
    Route::get('/{seoSetGroup}/{seoSet}/config', [SeoSetConfigController::class, 'edit'])->name('sets.config.edit');
    Route::patch('/{seoSetGroup}/{seoSet}/config', [SeoSetConfigController::class, 'update'])->name('sets.config.update');
    Route::post('/{seoSetGroup}/{seoSet}/enable', [SeoSetStateController::class, 'enable'])->name('sets.enable');
    Route::post('/{seoSetGroup}/{seoSet}/disable', [SeoSetStateController::class, 'disable'])->name('sets.disable');
    Route::get('/{seoSetGroup}/{seoSet}/{seoSetLocalization}', [SeoSetLocalizationController::class, 'edit'])->name('sets.localization.edit');
    Route::patch('/{seoSetGroup}/{seoSet}/{seoSetLocalization}', [SeoSetLocalizationController::class, 'update'])->name('sets.localization.update');
});
