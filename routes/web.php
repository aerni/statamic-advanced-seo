<?php

use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

Route::name('advanced-seo.')->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemaps/{id}.xml', [SitemapController::class, 'show'])->name('sitemap.show');
    Route::get('/sitemap.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');
});
