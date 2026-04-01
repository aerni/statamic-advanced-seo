<?php

use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;

uses(FakesComposerLock::class);

beforeEach(function () {
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
