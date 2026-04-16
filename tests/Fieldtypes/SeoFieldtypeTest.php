<?php

use Aerni\AdvancedSeo\Facades\Seo;
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
});

function makeSeoField(string $handle = 'seo_title', array $childField = [], array $extra = []): Field
{
    return new Field($handle, array_merge([
        'type' => 'seo',
        'field' => array_merge(['type' => 'token_input'], $childField),
        'default' => '@default',
    ], $extra));
}

function makeSeoFieldWithParent(string $handle = 'seo_title', array $childField = [], array $extra = []): Field
{
    $entry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('test-page')
        ->data(['title' => 'Test Page']);

    $entry->saveQuietly();

    $field = makeSeoField($handle, $childField, $extra);
    $field->setParent($entry);

    return $field;
}

// --- preProcess ---

it('preprocesses @default as inherited state', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->preProcess('@default');

    expect($result)->toHaveKey('source', 'default')
        ->and($result)->toHaveKey('value');
});

it('preprocesses null as inherited state', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->preProcess(null);

    expect($result)->toHaveKey('source', 'default')
        ->and($result)->toHaveKey('value');
});

it('preprocesses a custom string value as custom state', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->preProcess('Custom Title');

    expect($result)->toBe(['source' => 'custom', 'value' => 'Custom Title']);
});

it('preprocesses a custom boolean value as custom state', function () {
    $field = makeSeoFieldWithParent('seo_noindex', ['type' => 'toggle']);
    $result = $field->fieldtype()->preProcess(true);

    expect($result)->toBe(['source' => 'custom', 'value' => true]);
});

it('preprocesses false as custom state', function () {
    $field = makeSeoFieldWithParent('seo_noindex', ['type' => 'toggle']);
    $result = $field->fieldtype()->preProcess(false);

    expect($result)->toBe(['source' => 'custom', 'value' => false]);
});

// --- process ---

it('processes default source as @default sentinel', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->process(['source' => 'default', 'value' => null]);

    expect($result)->toBe('@default');
});

it('processes custom string value', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->process(['source' => 'custom', 'value' => 'Hello World']);

    expect($result)->toBe('Hello World');
});

it('processes empty custom string as @default', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->process(['source' => 'custom', 'value' => '']);

    expect($result)->toBe('@default');
});

it('processes null custom value as @default', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->process(['source' => 'custom', 'value' => null]);

    expect($result)->toBe('@default');
});

it('preserves false boolean in custom state', function () {
    $field = makeSeoFieldWithParent('seo_noindex', ['type' => 'toggle']);
    $result = $field->fieldtype()->process(['source' => 'custom', 'value' => false]);

    expect($result)->toBe(false);
});

it('preserves true boolean in custom state', function () {
    $field = makeSeoFieldWithParent('seo_noindex', ['type' => 'toggle']);
    $result = $field->fieldtype()->process(['source' => 'custom', 'value' => true]);

    expect($result)->toBe(true);
});

it('processes null data as null', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->process(null);

    expect($result)->toBeNull();
});

it('processes code fieldtype with empty code as @default', function () {
    $field = makeSeoFieldWithParent('seo_head', ['type' => 'code']);
    $result = $field->fieldtype()->process(['source' => 'custom', 'value' => ['code' => '', 'mode' => 'htmlmixed']]);

    expect($result)->toBe('@default');
});

// --- augment ---

it('augments @default by resolving cascade', function () {
    $field = makeSeoFieldWithParent('seo_title');
    $result = $field->fieldtype()->augment('@default');

    // The cascade resolves {{ title }} from the content defaults blueprint.
    expect($result)->toBe('Test Page | English');
});

it('augments custom value directly', function () {
    $field = makeSeoFieldWithParent();
    $result = $field->fieldtype()->augment('My Custom Title');

    expect($result)->toBe('My Custom Title');
});

it('augments null by falling through to field default', function () {
    $field = makeSeoFieldWithParent('seo_title');
    $result = $field->fieldtype()->augment(null);

    // null → field->defaultValue() → '@default' → cascade resolution → {{ title }}
    expect($result)->toBe('Test Page | English');
});

it('augments @default using origin cascade when entry is synced to origin', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->sites(['english', 'french'])->saveQuietly();

    // English (origin) localization: noindex=true. French localization: noindex=false.
    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $origin = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('home')
        ->data(['title' => 'Home', 'seo_noindex' => '@default']);
    $origin->saveQuietly();

    // French entry synced to origin (no own value for seo_noindex).
    $french = Entry::make()
        ->collection('pages')
        ->locale('french')
        ->slug('home')
        ->origin($origin);
    $french->saveQuietly();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    // Synced entries should resolve @default against the origin's cascade,
    // matching what the UI displays via originDefaultValue().
    expect($field->fieldtype()->augment('@default'))->toBe(true);
});

it('augments @default using local cascade when entry explicitly sets @default', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->sites(['english', 'french'])->saveQuietly();

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $origin = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('home')
        ->data(['title' => 'Home', 'seo_noindex' => '@default']);
    $origin->saveQuietly();

    // French entry explicitly stores @default (not synced to origin for this field).
    $french = Entry::make()
        ->collection('pages')
        ->locale('french')
        ->slug('home')
        ->origin($origin)
        ->data(['seo_noindex' => '@default']);
    $french->saveQuietly();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    // When @default is set explicitly (not inherited), resolve against the local cascade.
    expect($field->fieldtype()->augment('@default'))->toBe(false);
});

// --- preload ---

it('returns expected preload structure', function () {
    $field = makeSeoFieldWithParent();

    // preProcess to populate the value (needed for childMeta)
    $field->setValue($field->fieldtype()->preProcess('@default'));

    $result = $field->fieldtype()->preload();

    expect($result)->toHaveKeys(['component', 'defaultValue', 'defaultMeta', 'meta'])
        ->and($result['component'])->toBe('token_input-fieldtype');
});

it('returns the child component name for textarea', function () {
    $field = makeSeoFieldWithParent('seo_description', ['type' => 'textarea']);

    $field->setValue($field->fieldtype()->preProcess('@default'));

    $result = $field->fieldtype()->preload();

    expect($result['component'])->toBe('textarea-fieldtype');
});

it('returns the child component name for toggle', function () {
    $field = makeSeoFieldWithParent('seo_noindex', ['type' => 'toggle']);

    $field->setValue($field->fieldtype()->preProcess('@default'));

    $result = $field->fieldtype()->preload();

    expect($result['component'])->toBe('toggle-fieldtype');
});

// --- round-trip ---

it('round-trips @default through preProcess and process', function () {
    $field = makeSeoFieldWithParent();

    $preprocessed = $field->fieldtype()->preProcess('@default');

    expect($preprocessed['source'])->toBe('default');

    $processed = $field->fieldtype()->process($preprocessed);

    expect($processed)->toBe('@default');
});

it('round-trips custom value through preProcess and process', function () {
    $field = makeSeoFieldWithParent();

    $preprocessed = $field->fieldtype()->preProcess('Custom Title');

    expect($preprocessed)->toBe(['source' => 'custom', 'value' => 'Custom Title']);

    $processed = $field->fieldtype()->process($preprocessed);

    expect($processed)->toBe('Custom Title');
});
