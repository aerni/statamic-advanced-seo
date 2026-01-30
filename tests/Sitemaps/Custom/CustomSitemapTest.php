<?php

use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;

it('has a type of custom', function () {
    $sitemap = new CustomSitemap('test');

    expect($sitemap->type())->toBe('custom');
});

it('returns the provided handle', function () {
    $sitemap = new CustomSitemap('my-pages');

    expect($sitemap->handle())->toBe('my-pages');
});

it('starts with an empty url collection', function () {
    $sitemap = new CustomSitemap('test');

    expect($sitemap->urls())->toBeEmpty();
});

it('can add a url', function () {
    $sitemap = new CustomSitemap('test');
    $url = new CustomSitemapUrl('https://example.com/page');

    $sitemap->add($url);

    expect($sitemap->urls())->toHaveCount(1);
});

it('returns self when adding a url for chaining', function () {
    $sitemap = new CustomSitemap('test');
    $url = new CustomSitemapUrl('https://example.com/page');

    expect($sitemap->add($url))->toBe($sitemap);
});

it('prevents duplicate urls', function () {
    $sitemap = new CustomSitemap('test');
    $url = new CustomSitemapUrl('https://example.com/page');

    $sitemap->add($url)->add($url);

    expect($sitemap->urls())->toHaveCount(1);
});

it('can add multiple different urls', function () {
    $sitemap = new CustomSitemap('test');

    $sitemap->add(new CustomSitemapUrl('https://example.com/page-1'));
    $sitemap->add(new CustomSitemapUrl('https://example.com/page-2'));
    $sitemap->add(new CustomSitemapUrl('https://example.com/page-3'));

    expect($sitemap->urls())->toHaveCount(3);
});

it('sets the sitemap reference on each url', function () {
    $sitemap = new CustomSitemap('test');
    $sitemap->add(new CustomSitemapUrl('https://example.com/page'));

    expect($sitemap->urls()->first()->sitemap())->toBe($sitemap);
});
