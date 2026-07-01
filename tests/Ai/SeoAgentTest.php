<?php

use Aerni\AdvancedSeo\Ai\FieldSpec;
use Aerni\AdvancedSeo\Ai\SeoAgent;

function makeAgent(string $field = 'seo_title', array $content = [], array $seoFields = [], string $locale = 'en', array $userInstructions = []): SeoAgent
{
    return new SeoAgent(
        field: $field,
        content: collect(array_merge(['title' => str_repeat('This is enough content to pass the minimum character validation. ', 5)], $content)),
        seoFields: collect($seoFields),
        locale: $locale,
        userInstructions: $userInstructions,
    );
}

it('defines the four seo fields with their handles and character limits', function () {
    $fields = collect(SeoAgent::fields());

    expect($fields)->toHaveCount(4)->each->toBeInstanceOf(FieldSpec::class)
        ->and($fields->map->handle->all())->toBe(['seo_title', 'seo_description', 'seo_og_title', 'seo_og_description'])
        ->and($fields->map->characters->all())->toBe([60, 155, 60, 155])
        ->and($fields->map->sibling->all())->toBe(['seo_description', 'seo_title', 'seo_og_description', 'seo_og_title']);
});

it('extracts custom seo field values, ignoring defaults, nulls, and non-seo fields', function () {
    $values = SeoAgent::seoFieldValues([
        'title' => 'Some page title',
        'seo_title' => ['source' => 'custom', 'value' => 'My custom title'],
        'seo_description' => ['source' => 'default', 'value' => 'Inherited default'],
        'seo_og_title' => ['source' => 'custom', 'value' => 'Clean {{ title }}'],
        'seo_og_description' => ['source' => 'custom', 'value' => null],
    ]);

    expect($values->all())->toBe([
        'seo_title' => 'My custom title',
        'seo_og_title' => 'Clean',
    ]);
});

it('throws when content is below the minimum character threshold', function () {
    (new SeoAgent(
        field: 'seo_title',
        content: collect(['title' => 'Short']),
        seoFields: collect([]),
        locale: 'en',
    ))->generate();
})->throws(RuntimeException::class);

it('names the field being generated in the instructions', function () {
    expect(makeAgent('seo_description')->instructions())
        ->toContain('meta description for search engine results');
});

it('renders the content section', function () {
    expect(makeAgent('seo_title', ['body' => 'UNIQUE_CONTENT_VALUE'])->instructions())
        ->toContain('## Content')
        ->toContain('UNIQUE_CONTENT_VALUE');
});

it('renders the seo fields section', function () {
    expect(makeAgent('seo_title', seoFields: ['seo_description' => 'My existing description'])->instructions())
        ->toContain('## SEO fields')
        ->toContain('seo_description: My existing description');
});

it('omits the seo fields section when there are none', function () {
    expect(makeAgent('seo_title', ['body' => 'Just some content'])->instructions())
        ->not->toContain('## SEO fields');
});

it('renders the user instructions section', function () {
    expect(makeAgent(userInstructions: ['Global rule.', 'Scoped rule.'])->instructions())
        ->toContain("## User Instructions\nGlobal rule.\n\nScoped rule.");
});

it('omits the user instructions section when there are none', function () {
    expect(makeAgent(userInstructions: [])->instructions())->not->toContain('## User Instructions');
});

it('renders the rules section', function () {
    expect(makeAgent('seo_description')->instructions())
        ->toContain('## Rules')
        ->toContain('155 characters')
        ->toContain('meta title for search engine results')
        ->toContain('English')
        ->toContain('plain text');
});
