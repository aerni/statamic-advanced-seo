<?php

use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;
use Statamic\Facades\Site;

Route::name('advanced-seo.')->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-{type}-{handle}.xml', [SitemapController::class, 'show'])->name('sitemap.show');
    Route::get('/sitemap.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');

    Site::all()->each(function ($site) {
        Route::get("{$site->url()}/social-images/{theme}/{type}/{id}", [SocialImagesController::class, 'show']);
    });
});
