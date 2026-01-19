<?php

use Aerni\AdvancedSeo\Blueprints\BaseBlueprint;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;

it('can get untouched field definitions for GraphQL', function () {
    $fields = TestBlueprint::definition()->fields()->all();

    expect($fields)->toHaveCount(6);

    expect($fields->get('lazy')->get('instructions'))->toBe('fallback');
});

it('can resolve fields by context', function () {
    $context = new Context('collections', 'articles', Scope::CONTENT, 'english');

    $fields = TestBlueprint::resolve($context)->fields()->all();

    expect($fields->keys()->all())->toBe(['enabled', 'plain', 'lazy']);

    expect($fields->get('lazy')->get('instructions'))->toBe('collections');
});

class TestBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'handle';
    }

    protected function tabs(): array
    {
        return [
            'tab' => [
                [
                    'display' => 'Section One',
                    'fields' => [
                        ['handle' => 'enabled', 'field' => ['feature' => EnabledFeature::class]],
                        ['handle' => 'disabled', 'field' => ['feature' => DisabledFeature::class]],
                        ['handle' => 'plain', 'field' => ['instructions' => 'plain']],
                        ['handle' => 'lazy', 'field' => ['instructions' => $this->lazy(fn (?Context $context) => $context->type, 'fallback')]],
                    ],
                ],
                [
                    'display' => 'Section Two',
                    'fields' => [
                        ['handle' => 'disabled_2', 'field' => ['feature' => DisabledFeature::class]],
                        ['handle' => 'disabled_3', 'field' => ['feature' => DisabledFeature::class]],
                    ],
                ],
            ],
        ];
    }
}

class EnabledFeature
{
    public static function enabled(?Context $context = null): bool
    {
        return true;
    }
}

class DisabledFeature
{
    public static function enabled(?Context $context = null): bool
    {
        return false;
    }
}
