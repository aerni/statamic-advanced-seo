<?php

use Statamic\Facades\URL;
use Statamic\Facades\Site;
use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Http\Controllers\Web\SitemapController;
use Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController;

Route::name('advanced-seo.')->group(function () {
    Route::get(URL::tidy(Site::current()->url() . '/social-images/{type}/{id}'), [SocialImagesController::class, 'show'])->name('social_images.show');

    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-{type}-{handle}.xml', [SitemapController::class, 'show'])->name('sitemap.show');
    Route::get('/sitemap.xsl', [SitemapController::class, 'xsl'])->name('sitemap.xsl');
});
