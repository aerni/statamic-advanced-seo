<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();

    $this->installAiPackage();
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    expect(Ai::enabled())->toBeFalse();
});

it('is disabled by default', function () {
    expect(Ai::enabled())->toBeFalse();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.ai.enabled' => false]);

    expect(Ai::enabled())->toBeFalse();
});

it('is disabled when the ai package is not installed', function () {
    $this->uninstallPackages();

    expect(Ai::enabled())->toBeFalse();
});

it('is disabled when enabled but no provider key is configured', function () {
    config(['advanced-seo.ai.enabled' => true]);

    expect(Ai::enabled())->toBeFalse();
});

it('is enabled when enabled and provider key is configured', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    expect(Ai::enabled())->toBeTrue();
});

it('uses explicit provider over default when checking if configured', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'advanced-seo.ai.provider' => 'anthropic',
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'openai-key',
        'ai.providers.anthropic.key' => null,
    ]);

    expect(Ai::enabled())->toBeFalse();
});

it('uses explicit provider key when provider is set', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'advanced-seo.ai.provider' => 'anthropic',
        'ai.default' => 'openai',
        'ai.providers.openai.key' => null,
        'ai.providers.anthropic.key' => 'anthropic-key',
    ]);

    expect(Ai::enabled())->toBeTrue();
});

it('is enabled when no context is provided', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    expect(Ai::enabled(null))->toBeTrue();
});

it('is enabled in config scope even when seoSet ai is disabled', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    Seo::find('collections::pages')
        ->config()
        ->set('ai', false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );

    expect(Ai::enabled($context))->toBeTrue();
});

it('is disabled if the seoSet is disabled', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    Seo::find('collections::pages')
        ->config()
        ->enabled(false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Localization,
        site: 'english',
    );

    expect(Ai::enabled($context))->toBeFalse();
});

it('is disabled if ai is disabled in the config', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    Seo::find('collections::pages')
        ->config()
        ->set('ai', false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Content,
        site: 'english',
    );

    expect(Ai::enabled($context))->toBeFalse();
});

it('shows in all contexts when enabled', function () {
    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    Seo::find('collections::pages')
        ->config()
        ->set('ai', true)
        ->save();

    foreach ([Scope::Config, Scope::Localization, Scope::Content] as $scope) {
        $context = new Context(
            parent: Collection::find('pages'),
            type: 'collections',
            handle: 'pages',
            scope: $scope,
            site: 'english',
        );

        expect(Ai::enabled($context))->toBeTrue("Failed for scope: {$scope->value}");
    }
});
