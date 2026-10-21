<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\SetHreflangXDefault;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
        'english' => ['name' => 'English', 'url' => 'https://example.com/en', 'locale' => 'en'],
    ]);
});

it('stores the default site as x-default', function () {
    (new SetHreflangXDefault('aerni/advanced-seo'))->update();

    flushBlink();

    expect(Seo::find('site::defaults')->config()->get('hreflang_x_default'))->toBe('dutch');
});

it('keeps an existing x-default site', function () {
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'english')->save();

    (new SetHreflangXDefault('aerni/advanced-seo'))->update();

    flushBlink();

    expect(Seo::find('site::defaults')->config()->get('hreflang_x_default'))->toBe('english');
});

it('skips single site installs', function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
    ]);
    flushBlink();

    (new SetHreflangXDefault('aerni/advanced-seo'))->update();

    flushBlink();

    expect(Seo::find('site::defaults')->config()->get('hreflang_x_default'))->toBeNull();
});
