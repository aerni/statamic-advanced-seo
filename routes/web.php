<?php

use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

if (config('advanced-seo.sitemap.enabled') && in_array(app()->environment(), config('advanced-seo.crawling.environments', []))) {
    Route::name('advanced-seo.')->group(function () {
        Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
        Route::get('/sitemaps/{id}.xml', [SitemapController::class, 'show'])->name('sitemap.show');
        Route::get('/sitemap.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');
    });
}
