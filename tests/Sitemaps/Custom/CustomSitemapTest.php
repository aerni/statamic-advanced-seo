<?php

use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Custom\SitemapBuilder;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);
});

it('has a type of custom', function () {
    $sitemap = new SitemapBuilder('test');

    expect($sitemap->type())->toBe('custom');
});

it('returns the provided handle', function () {
    $sitemap = new SitemapBuilder('my-pages');

    expect($sitemap->handle())->toBe('my-pages');
});

it('defaults site to the default statamic site', function () {
    $sitemap = new SitemapBuilder('test');

    expect($sitemap->site())->toBe(Site::default()->handle());
});

it('can set and get site fluently', function () {
    $sitemap = new SitemapBuilder('test');

    expect($sitemap->site('english'))->toBe($sitemap)
        ->and($sitemap->site())->toBe('english');
});

it('throws an exception for an invalid site', function () {
    $sitemap = new SitemapBuilder('test');

    $sitemap->site('nonexistent');
})->throws(InvalidArgumentException::class, 'Invalid site: nonexistent');

it('starts with an empty url collection', function () {
    $sitemap = new SitemapBuilder('test');

    expect($sitemap->urls())->toBeEmpty();
});

it('can add a url from a string', function () {
    $sitemap = new SitemapBuilder('test');

    $sitemap->add('https://example.com/page');

    expect($sitemap->urls())->toHaveCount(1)
        ->and($sitemap->urls()->first()->loc())->toBe('https://example.com/page');
});

it('can add a url with a closure for customization', function () {
    $sitemap = new SitemapBuilder('test');

    $sitemap->add('https://example.com/page', function (CustomSitemapUrl $url) {
        $url->changefreq('weekly')
            ->priority('0.8');
    });

    $url = $sitemap->urls()->first();

    expect($url->loc())->toBe('https://example.com/page')
        ->and($url->changefreq())->toBe('weekly')
        ->and($url->priority())->toBe('0.8');
});

it('returns self when adding a url for chaining', function () {
    $sitemap = new SitemapBuilder('test');

    expect($sitemap->add('https://example.com/page'))->toBe($sitemap);
});

it('prevents duplicate urls', function () {
    $sitemap = new SitemapBuilder('test');

    $sitemap->add('https://example.com/page')->add('https://example.com/page');

    expect($sitemap->urls())->toHaveCount(1);
});

it('can add multiple different urls', function () {
    $sitemap = new SitemapBuilder('test');

    $sitemap->add('https://example.com/page-1');
    $sitemap->add('https://example.com/page-2');
    $sitemap->add('https://example.com/page-3');

    expect($sitemap->urls())->toHaveCount(3);
});

it('sets the sitemap reference on each url', function () {
    $sitemap = new SitemapBuilder('test');
    $sitemap->add('https://example.com/page');

    expect($sitemap->urls()->first()->sitemap())->toBe($sitemap);
});
