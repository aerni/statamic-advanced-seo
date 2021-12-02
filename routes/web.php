<?php

use Statamic\Facades\Site;
use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;

Site::all()->map(function ($site) {
    // Social Images Generator
    Route::get($site->url() . '/social-images/{type}/{id}', [SocialImagesController::class, 'show'])->name('advanced-seo.social_images.show');

    // Sitemap
    Route::get($site->url() . '/sitemap.xml', [SitemapController::class, 'index'])->name('advanced-seo.sitemap.index');
    Route::get($site->url() . '/sitemap_{type}_{handle}.xml', [SitemapController::class, 'show'])->name('advanced-seo.sitemap.show');
    Route::get($site->url() . '/sitemap.xsl', [SitemapController::class, 'xsl'])->name('advanced-seo.sitemap.xsl');
});
