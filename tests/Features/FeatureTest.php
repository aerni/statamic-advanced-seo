<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Feature;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
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

it('returns available() for site-type contexts', function () {
    $context = new Context(
        parent: Seo::find('site::defaults'),
        type: 'site',
        handle: 'defaults',
        scope: Scope::Config,
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
        scope: Scope::Content,
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
        scope: Scope::Content,
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
        scope: Scope::Content,
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
        scope: Scope::Config,
        site: 'english',
    );

    expect(AvailableFeature::enabled($context))->toBeTrue();
});

it('consults enabledInLocalization() for content-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Content,
        site: 'english',
    );

    expect(FeatureWithLocalizationCheckTrue::enabled($context))->toBeTrue();
    expect(FeatureWithLocalizationCheckFalse::enabled($context))->toBeFalse();
});

it('consults enabledInConfig() for config-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );

    expect(FeatureWithConfigCheckTrue::enabled($context))->toBeTrue();
    expect(FeatureWithConfigCheckFalse::enabled($context))->toBeFalse();
});

it('does not call enabledInConfig() for content-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Content,
        site: 'english',
    );

    // enabledInConfig would return false; content scope uses enabledInLocalization instead,
    // which defaults to true.
    expect(FeatureWithConfigCheckFalse::enabled($context))->toBeTrue();
});

it('does not call enabledInLocalization() for config-scope contexts', function () {
    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );

    // enabledInLocalization would return false; config scope uses enabledInConfig instead,
    // which defaults to true.
    expect(FeatureWithLocalizationCheckFalse::enabled($context))->toBeTrue();
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

    protected static function enabledInConfig(SeoSet $set): bool
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

    protected static function enabledInConfig(SeoSet $set): bool
    {
        return false;
    }
}

class FeatureWithLocalizationCheckTrue extends Feature
{
    protected static function available(): bool
    {
        return true;
    }

    protected static function enabledInLocalization(SeoSet $set): bool
    {
        return true;
    }
}

class FeatureWithLocalizationCheckFalse extends Feature
{
    protected static function available(): bool
    {
        return true;
    }

    protected static function enabledInLocalization(SeoSet $set): bool
    {
        return false;
    }
}
