<?php

use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Statamic\Facades\URL;
use Statamic\Facades\Site;
use Illuminate\Support\Facades\Route;

Route::name('advanced-seo.')->group(function () {
    Route::get(URL::tidy(Site::current()->url() . '/sitemap.xml'), [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get(URL::tidy(Site::current()->url() . '/sitemap_{type}_{handle}.xml'), [SitemapController::class, 'show'])->name('sitemap.show');
    Route::get(URL::tidy(Site::current()->url() . '/sitemap.xsl'), [SitemapController::class, 'xsl'])->name('sitemap.xsl');
});
