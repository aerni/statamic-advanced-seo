<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tokens\TokenParser;
use Aerni\AdvancedSeo\Tokens\ValueToken;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Fields\Field;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

class ParserTestValueToken extends ValueToken
{
    public function handle(): string
    {
        return 'parser_test';
    }

    public function value(): string
    {
        return 'Parser Test Value';
    }
}

class OrganizationNameToken extends ValueToken
{
    public function handle(): string
    {
        return 'organization_name';
    }

    public function value(): string
    {
        return 'Acme';
    }
}

class CountingValueToken extends ValueToken
{
    public static int $calls = 0;

    public function handle(): string
    {
        return 'counted_token';
    }

    public function value(): string
    {
        static::$calls++;

        return 'counted';
    }
}

class ReenteringValueToken extends ValueToken
{
    public static int $calls = 0;

    public function handle(): string
    {
        return 'company_name';
    }

    public function value(): ?string
    {
        static::$calls++;

        if (static::$calls > 3) {
            return 'looped';
        }

        $value = $this->parent->augmentedValue('seo_description')?->value();

        return is_string($value) ? $value : null;
    }
}

class TitleShadowToken extends ValueToken
{
    public function handle(): string
    {
        return 'title';
    }

    public function value(): string
    {
        return 'From Token';
    }
}

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

function makeEntry(array $data = []): Statamic\Entries\Entry
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
    $field = makeField(parent: new stdClass);

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

it('resolves custom value tokens', function () {
    config(['advanced-seo.tokens' => [ParserTestValueToken::class]]);

    $entry = makeEntry();
    $field = makeField(parent: $entry);

    $result = $this->parser->parse('{{ parser_test }}', $field);

    expect($result)->toBe('Parser Test Value');
});

it('lets a value token win over a site default with the same handle', function () {
    config(['advanced-seo.tokens' => [OrganizationNameToken::class]]);

    Seo::find('site::defaults')->in('english')->set('organization_name', 'Widgets')->save();

    $entry = makeEntry();
    $field = makeField(parent: $entry);

    expect($this->parser->parse('{{ organization_name }}', $field))->toBe('Acme');
});

it('does not evaluate value tokens the string does not reference', function () {
    CountingValueToken::$calls = 0;
    config(['advanced-seo.tokens' => [CountingValueToken::class]]);

    $entry = makeEntry(['title' => 'My Page']);
    $field = makeField(parent: $entry);

    expect($this->parser->parse('{{ title }}', $field))->toBe('My Page')
        ->and(CountingValueToken::$calls)->toBe(0);
});

it('does not recurse when a value token augments another token field', function () {
    ReenteringValueToken::$calls = 0;
    config(['advanced-seo.tokens' => [ReenteringValueToken::class]]);

    $entry = makeEntry(['seo_description' => '{{ company_name }}']);
    $field = makeField(handle: 'seo_title', parent: $entry);

    $result = $this->parser->parse('{{ company_name }}', $field);

    expect(ReenteringValueToken::$calls)->toBe(1)
        ->and($result)->toBe('');
});

it('gives an entry field precedence over a value token with the same handle', function () {
    config(['advanced-seo.tokens' => [TitleShadowToken::class]]);

    $entry = makeEntry(['title' => 'My Page']);
    $field = makeField(parent: $entry);

    expect($this->parser->parse('{{ title }}', $field))->toBe('My Page');
});

it('applies modifiers to custom value tokens', function () {
    config(['advanced-seo.tokens' => [ParserTestValueToken::class]]);

    $entry = makeEntry();
    $field = makeField(parent: $entry);

    expect($this->parser->parse('{{ parser_test | upper }}', $field))->toBe('PARSER TEST VALUE');
});

it('leaves unresolvable tokens as empty strings', function () {
    $entry = makeEntry();
    $field = makeField(parent: $entry);

    $result = $this->parser->parse('{{ nonexistent_field }}', $field);

    expect($result)->toBe('');
});
