<?php

use Aerni\AdvancedSeo\Ai\FieldSpec;
use Aerni\AdvancedSeo\Ai\SeoAgent;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();

    $this->blueprint = Collection::findByHandle('pages')->entryBlueprint();
});

function makeAgent(string $field = 'seo_title', array $content = [], ?string $site = 'english', $blueprint = null, ?string $additionalInstructions = null): SeoAgent
{
    return new SeoAgent(
        field: $field,
        blueprint: $blueprint ?? test()->blueprint,
        content: array_merge(['title' => str_repeat('This is enough content to pass the minimum character validation. ', 5)], $content),
        site: $site,
        additionalInstructions: $additionalInstructions,
    );
}

// --- fields() ---

it('returns four field specs', function () {
    $fields = SeoAgent::fields();

    expect($fields)->toHaveCount(4)
        ->and($fields)->each->toBeInstanceOf(FieldSpec::class);
});

it('returns the correct field handles', function () {
    $handles = array_column(SeoAgent::fields(), 'handle');

    expect($handles)->toBe(['seo_title', 'seo_description', 'seo_og_title', 'seo_og_description']);
});

it('returns field specs with character limits', function () {
    $fields = SeoAgent::fields();

    expect($fields[0]->characters)->toBe(60)
        ->and($fields[1]->characters)->toBe(155)
        ->and($fields[2]->characters)->toBe(60)
        ->and($fields[3]->characters)->toBe(155);
});

// --- FieldSpec ---

it('creates a field spec with correct properties', function () {
    $spec = new FieldSpec('seo_title', 'meta title', 60);

    expect($spec->handle)->toBe('seo_title')
        ->and($spec->purpose)->toBe('meta title')
        ->and($spec->characters)->toBe(60);
});

// --- generate() validation ---

it('throws when content is below minimum character threshold', function () {
    $agent = new SeoAgent(
        field: 'seo_title',
        blueprint: $this->blueprint,
        content: ['title' => 'Short'],
        site: 'english',
    );

    $agent->generate();
})->throws(RuntimeException::class);

it('includes remaining character count in the exception message', function () {
    $agent = new SeoAgent(
        field: 'seo_title',
        blueprint: $this->blueprint,
        content: ['title' => 'Short'],
        site: 'english',
    );

    try {
        $agent->generate();
    } catch (RuntimeException $e) {
        // The message should contain a number representing the missing characters.
        expect($e->getMessage())->toBeString();
    }
});

// --- processContent() ---

it('excludes the target field from content', function () {
    $agent = makeAgent('seo_title', [
        'seo_title' => ['value' => 'Existing Title'],
    ]);

    $instructions = $agent->instructions();

    expect($instructions)->not->toContain('seo_title: Existing Title');
});

it('strips Antlers tokens from seo field values', function () {
    $agent = makeAgent('seo_description', [
        'seo_title' => ['value' => '{{ title }} | {{ site_name }}'],
    ]);

    $instructions = $agent->instructions();

    expect($instructions)->not->toContain('{{');
});

it('includes other seo fields in the content section', function () {
    $agent = makeAgent('seo_description', [
        'seo_title' => ['value' => 'My Page Title'],
    ]);

    $instructions = $agent->instructions();

    expect($instructions)->toContain('seo_title:');
});

it('handles null seo field values without error', function () {
    $agent = makeAgent('seo_title', [
        'seo_description' => ['value' => null],
    ]);

    expect($agent->instructions())->toBeString();
});

it('handles non-string content field values without error', function () {
    $agent = makeAgent('seo_title', [
        'seo_json_ld' => ['code' => '{"@type": "Article"}', 'mode' => 'javascript'],
    ]);

    expect($agent->instructions())->toBeString();
});

// --- instructions() ---

it('generates instructions with a content section', function () {
    $agent = makeAgent();

    expect($agent->instructions())->toContain('## Content');
});

it('generates instructions with a rules section', function () {
    $agent = makeAgent();

    expect($agent->instructions())->toContain('## Rules');
});

it('includes the character limit in the rules', function () {
    $agent = makeAgent('seo_title');

    expect($agent->instructions())->toContain('60 characters');
});

it('includes the language in the rules', function () {
    $agent = makeAgent();

    expect($agent->instructions())->toContain('English');
});

it('includes the plain text output rule', function () {
    $agent = makeAgent();

    expect($agent->instructions())->toContain('plain text');
});

it('includes the complementary pair rule', function () {
    $agent = makeAgent();

    expect($agent->instructions())->toContain('seo_title + seo_description');
});

// --- instructionsSection() ---

it('includes custom AI instructions in the prompt', function () {
    $agent = makeAgent(additionalInstructions: 'Always use the brand name "Acme" at the end of titles.');

    $instructions = $agent->instructions();

    expect($instructions)->toContain('## Instructions')
        ->and($instructions)->toContain('Always use the brand name "Acme" at the end of titles.');
});

it('does not include an instructions section when AI instructions are null', function () {
    $agent = makeAgent(additionalInstructions: null);

    expect($agent->instructions())->not->toContain('## Instructions');
});

it('places the instructions section between content and rules', function () {
    $agent = makeAgent(additionalInstructions: 'Use formal language.');

    $instructions = $agent->instructions();

    $contentPos = strpos($instructions, '## Content');
    $instructionsPos = strpos($instructions, '## Instructions');
    $rulesPos = strpos($instructions, '## Rules');

    expect($contentPos)->toBeLessThan($instructionsPos)
        ->and($instructionsPos)->toBeLessThan($rulesPos);
});
