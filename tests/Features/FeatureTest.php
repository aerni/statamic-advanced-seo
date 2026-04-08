<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Feature;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
});

it('returns available() when no context is provided', function () {
    expect(AvailableFeature::enabled())->toBeTrue();
    expect(UnavailableFeature::enabled())->toBeFalse();
});

it('returns available() when the context is config-scope', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::CONFIG,
        site: 'english',
    );

    expect(AvailableFeature::enabled($context))->toBeTrue();
    expect(UnavailableFeature::enabled($context))->toBeFalse();
});

it('returns available() when the context has no associated seoset', function () {
    // A bare Collection with no SeoSet on disk — $context->seoSet() returns null.
    $context = new Context(
        parent: Collection::make('articles'),
        type: 'collections',
        handle: 'articles',
        scope: Scope::CONTENT,
        site: 'english',
    );

    expect(AvailableFeature::enabled($context))->toBeTrue();
    expect(UnavailableFeature::enabled($context))->toBeFalse();
});

it('composes available() and the seoset checks for content-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::CONTENT,
        site: 'english',
    );

    expect(AvailableFeature::enabled($context))->toBeTrue();
    expect(UnavailableFeature::enabled($context))->toBeFalse();
});

it('returns false when the seoset is disabled for content-scope contexts', function () {
    Seo::find('collections::pages')
        ->config()
        ->enabled(false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::CONTENT,
        site: 'english',
    );

    expect(AvailableFeature::enabled($context))->toBeFalse();
});

it('returns available() for config-scope contexts even when the seoset is disabled', function () {
    Seo::find('collections::pages')
        ->config()
        ->enabled(false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::CONFIG,
        site: 'english',
    );

    expect(AvailableFeature::enabled($context))->toBeTrue();
});

it('consults enabledInConfig() for content-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::CONTENT,
        site: 'english',
    );

    expect(FeatureWithConfigCheckTrue::enabled($context))->toBeTrue();
    expect(FeatureWithConfigCheckFalse::enabled($context))->toBeFalse();
});

it('bypasses enabledInConfig() for config-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::CONFIG,
        site: 'english',
    );

    // Config scope bypasses the SeoSet checks entirely, so a feature whose
    // enabledInConfig() would return false still reports enabled.
    expect(FeatureWithConfigCheckFalse::enabled($context))->toBeTrue();
});

class AvailableFeature extends Feature
{
    protected static function available(): bool
    {
        return true;
    }
}

class UnavailableFeature extends Feature
{
    protected static function available(): bool
    {
        return false;
    }
}

class FeatureWithConfigCheckTrue extends Feature
{
    protected static function available(): bool
    {
        return true;
    }

    protected static function enabledInConfig(SeoSetConfig $config): bool
    {
        return true;
    }
}

class FeatureWithConfigCheckFalse extends Feature
{
    protected static function available(): bool
    {
        return true;
    }

    protected static function enabledInConfig(SeoSetConfig $config): bool
    {
        return false;
    }
}
