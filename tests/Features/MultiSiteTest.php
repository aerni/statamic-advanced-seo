<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Features\MultiSite;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('is disabled on the free edition', function () {
    useFreeEdition();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    expect(MultiSite::enabled())->toBeFalse();
});

it('is disabled with a single site', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    expect(MultiSite::enabled())->toBeFalse();
});

it('is enabled on pro with multiple sites', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    expect(MultiSite::enabled())->toBeTrue();
});

it('is disabled for a seo set that is scoped to a single site', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );

    expect(MultiSite::enabled($context))->toBeFalse();
});

it('is enabled for a seo set that spans multiple sites', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('articles')->sites(['english', 'german'])->saveQuietly();

    $context = new Context(
        parent: Collection::find('articles'),
        type: 'collections',
        handle: 'articles',
        scope: Scope::Config,
        site: 'english',
    );

    expect(MultiSite::enabled($context))->toBeTrue();
});
