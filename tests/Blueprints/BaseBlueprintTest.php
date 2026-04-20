<?php

use Aerni\AdvancedSeo\Blueprints\BaseBlueprint;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Features\Feature;
use Statamic\Facades\Collection;

it('can get field definitions filtered by global config for GraphQL', function () {
    $fields = TestBlueprint::definition()->fields()->all();

    expect($fields)->toHaveCount(3);

    expect($fields->get('lazy')->get('instructions'))->toBe('fallback');
});

it('can resolve fields by context', function () {
    $context = new Context(
        parent: Collection::make('articles'),
        type: 'collections',
        handle: 'articles',
        scope: Scope::Content,
        site: 'english',
    );

    $fields = TestBlueprint::resolve($context)->fields()->all();

    expect($fields->keys()->all())->toBe(['enabled', 'plain', 'lazy']);

    expect($fields->get('lazy')->get('instructions'))->toBe('collections');
});

function testBlueprintContext(): Context
{
    return new Context(
        parent: Collection::make('articles'),
        type: 'collections',
        handle: 'articles',
        scope: Scope::Content,
        site: 'english',
    );
}

it('marks all fields as hidden when hidden() is called', function () {
    $fields = TestBlueprint::make()->for(testBlueprintContext())->hidden()->get()->fields()->all();

    expect($fields->keys()->all())->toBe(['enabled', 'plain', 'lazy']);

    foreach ($fields as $field) {
        expect($field->get('visibility'))->toBe('hidden');
    }
});

it('strips validate rules when hidden() is called', function () {
    $fields = TestBlueprint::make()->for(testBlueprintContext())->hidden()->get()->fields()->all();

    expect($fields->get('plain')->get('validate'))->toBeNull();
});

it('treats hidden(false) as a no-op', function () {
    $fields = TestBlueprint::make()->for(testBlueprintContext())->hidden(false)->get()->fields()->all();

    expect($fields->get('plain')->get('visibility'))->toBe('visible');
    expect($fields->get('plain')->get('validate'))->toBe(['required']);
});

it('overrides per-field lazy visibility when hidden', function () {
    $fields = TestBlueprint::make()->for(testBlueprintContext())->hidden()->get()->fields()->all();

    // The `plain` field declares `visibility` via $this->lazy() — the global hide must win.
    expect($fields->get('plain')->get('visibility'))->toBe('hidden');
});

it('preserves feature filtering when hidden', function () {
    $fields = TestBlueprint::make()->for(testBlueprintContext())->hidden()->get()->fields()->all();

    // disabled / disabled_2 / disabled_3 stay filtered out; only enabled features pass through.
    expect($fields->keys()->all())->toBe(['enabled', 'plain', 'lazy']);
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
                        ['handle' => 'plain', 'field' => [
                            'instructions' => 'plain',
                            'visibility' => $this->lazy(fn () => 'visible', 'visible'),
                            'validate' => ['required'],
                        ]],
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

class EnabledFeature extends Feature
{
    protected static function available(): bool
    {
        return true;
    }
}

class DisabledFeature extends Feature
{
    protected static function available(): bool
    {
        return false;
    }
}
