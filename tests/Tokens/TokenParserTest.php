<?php

use Aerni\AdvancedSeo\Tokens\TokenParser;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Fields\Field;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Blink::flush();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    AssetContainer::make('assets')->disk('local')->saveQuietly();

    $this->parser = app(TokenParser::class);
});

function makeField(string $handle = 'seo_title', string $type = 'token_input', mixed $parent = null): Field
{
    $field = new Field($handle, ['type' => $type]);

    if ($parent) {
        $field->setParent($parent);
    }

    return $field;
}

function makeEntry(array $data = []): \Statamic\Entries\Entry
{
    $entry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('test-page')
        ->data(array_merge(['title' => 'Test Page'], $data));

    $entry->saveQuietly();

    return $entry;
}

// --- Null handling ---

it('returns null when data is null', function () {
    $entry = makeEntry();
    $field = makeField(parent: $entry);

    expect($this->parser->parse(null, $field))->toBeNull();
});

// --- Non-entry/term parent ---

it('returns data unchanged when parent is not an entry or term', function () {
    $field = makeField();

    // No parent set — parent() returns null
    expect($this->parser->parse('Hello {{ title }}', $field))->toBe('Hello {{ title }}');
});

it('returns data unchanged when parent is a generic object', function () {
    $field = makeField(parent: new \stdClass);

    expect($this->parser->parse('{{ title }}', $field))->toBe('{{ title }}');
});

// --- No Antlers syntax ---

it('returns data unchanged when no Antlers syntax is present', function () {
    $entry = makeEntry();
    $field = makeField(parent: $entry);

    expect($this->parser->parse('Plain text without tokens', $field))->toBe('Plain text without tokens');
});

it('returns empty string as-is', function () {
    $entry = makeEntry();
    $field = makeField(parent: $entry);

    expect($this->parser->parse('', $field))->toBe('');
});

// --- Circular reference stripping ---

it('strips self-referencing tokens to prevent infinite recursion', function () {
    $entry = makeEntry();
    $field = makeField(handle: 'seo_title', parent: $entry);

    $result = $this->parser->parse('{{ seo_title }} is great', $field);

    expect($result)->toBe(' is great');
});

it('strips self-references with varied whitespace', function () {
    $entry = makeEntry();
    $field = makeField(handle: 'seo_title', parent: $entry);

    $result = $this->parser->parse('{{seo_title}} and {{  seo_title  }}', $field);

    expect($result)->toBe(' and ');
});

// --- Token resolution ---

it('resolves entry field tokens via Antlers', function () {
    $entry = makeEntry(['title' => 'My Page']);
    $field = makeField(parent: $entry);

    $result = $this->parser->parse('{{ title }}', $field);

    expect($result)->toBe('My Page');
});

it('resolves multiple tokens in a single string', function () {
    $entry = makeEntry(['title' => 'My Page']);
    $field = makeField(parent: $entry);

    $result = $this->parser->parse('{{ title }} - {{ title }}', $field);

    expect($result)->toBe('My Page - My Page');
});

it('leaves unresolvable tokens as empty strings', function () {
    $entry = makeEntry();
    $field = makeField(parent: $entry);

    $result = $this->parser->parse('{{ nonexistent_field }}', $field);

    expect($result)->toBe('');
});
