<?php

use Statamic\Facades\Site;
use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;

// Social Images Generator
if (config('advanced-seo.social_images.generator.enabled', false)) {
    Site::all()->each(function ($site) {
        Route::statamic("{$site->url()}/seo/social-images/og/{id}", 'social_images/og', [
            'layout' => 'social_images/layout',
        ]);

        Route::statamic("{$site->url()}/seo/social-images/twitter/{id}", 'social_images/twitter', [
            'layout' => 'social_images/layout',
        ]);
    });
}

// Sitemap
Site::all()->map(function ($site) {
    Route::get($site->url() . '/sitemap.xml', [SitemapController::class, 'index'])->name('advanced-seo.sitemap.index');
    Route::get($site->url() . '/sitemap_{type}_{handle}.xml', [SitemapController::class, 'show'])->name('advanced-seo.sitemap.show');
    Route::get($site->url() . '/sitemap.xsl', [SitemapController::class, 'xsl'])->name('advanced-seo.sitemap.xsl');
});
