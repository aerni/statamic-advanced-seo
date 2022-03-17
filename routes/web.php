<?php

use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;

Route::name('advanced-seo.')->group(function () {
    Site::all()->map(function ($site) {
        Route::get($site->url() . '/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
        Route::get($site->url() . '/sitemap_{type}_{handle}.xml', [SitemapController::class, 'show'])->name('sitemap.show');
        Route::get($site->url() . '/sitemap.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');
    });
});
