<?php

use Statamic\Facades\Site;
use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;

Route::name('advanced-seo.')->group(function () {
    Site::all()->map(function ($site) {
        // Social Images Generator
        Route::get($site->url() . '/social-images/{type}/{id}', [SocialImagesController::class, 'show'])->name('social_images.show');

        // Sitemap
        Route::get($site->url() . '/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
        Route::get($site->url() . '/sitemap_{type}_{handle}.xml', [SitemapController::class, 'show'])->name('sitemap.show');
        Route::get($site->url() . '/sitemap.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');
    });
});
