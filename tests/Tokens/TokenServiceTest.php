<?php

use Aerni\AdvancedSeo\Registries\TokenRegistry;
use Aerni\AdvancedSeo\Tokens\Tokens;
use Aerni\AdvancedSeo\Tokens\TokenService;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\Fields\Value;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Blink::flush();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    AssetContainer::make('assets')->disk('local')->saveQuietly();

    $this->service = app(TokenService::class);
});

it('returns the token registry', function () {
    expect($this->service->registry())->toBeInstanceOf(TokenRegistry::class);
});

it('creates a Tokens instance for a given parent', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('test');

    $entry->saveQuietly();

    expect($this->service->for($entry))->toBeInstanceOf(Tokens::class);
});

it('normalizes a value using the correct normalizer', function () {
    $fieldtype = Mockery::mock(Fieldtype::class);
    $fieldtype->shouldReceive('handle')->andReturn('text');

    $value = Mockery::mock(Value::class);
    $value->shouldReceive('fieldtype')->andReturn($fieldtype);
    $value->shouldReceive('value')->andReturn('<b>Hello</b>');

    expect($this->service->normalize($value))->toBe('Hello');
});

it('returns null when normalizing a value with no matching normalizer', function () {
    $fieldtype = Mockery::mock(Fieldtype::class);
    $fieldtype->shouldReceive('handle')->andReturn('video');

    $value = Mockery::mock(Value::class);
    $value->shouldReceive('fieldtype')->andReturn($fieldtype);

    expect($this->service->normalize($value))->toBeNull();
});

it('returns null when normalizing a value with null fieldtype', function () {
    $value = Mockery::mock(Value::class);
    $value->shouldReceive('fieldtype')->andReturn(null);

    expect($this->service->normalize($value))->toBeNull();
});

it('parses token strings via the parser', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('test')
        ->data(['title' => 'Hello World']);

    $entry->saveQuietly();

    $field = new Field('seo_title', ['type' => 'token_input']);
    $field->setParent($entry);

    expect($this->service->parse('{{ title }}', $field))->toBe('Hello World');
});

it('returns null when parsing null data', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('test');

    $entry->saveQuietly();

    $field = new Field('seo_title', ['type' => 'token_input']);
    $field->setParent($entry);

    expect($this->service->parse(null, $field))->toBeNull();
});
