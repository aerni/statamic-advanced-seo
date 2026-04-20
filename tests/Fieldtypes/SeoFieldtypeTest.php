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

/**
 * Set up an english (origin) + french two-site collection with a 'home' entry
 * in each locale. Returns [origin, french]. Callers seed SeoSet localization
 * values per-test to exercise cascade differences.
 */
function makeTwoSiteHomeEntries(array $originData = [], array $frenchData = []): array
{
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->sites(['english', 'french'])->saveQuietly();

    $origin = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->slug('home')
        ->data(array_merge(['title' => 'Home'], $originData));

    $origin->saveQuietly();

    $french = Entry::make()
        ->collection('pages')
        ->locale('french')
        ->slug('home')
        ->origin($origin)
        ->data($frenchData);

    $french->saveQuietly();

    return [$origin, $french];
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

// --- origin / sync: non-text fields ---
//
// Non-text fields (toggles, selects) without a placeholder need the UI and the
// augmented value to agree on what @default resolves to when an entry is synced
// to its origin. Both pipelines must swap to the origin's cascade in that case.

it('augments @default using origin cascade for synced non-text field', function () {
    [, $french] = makeTwoSiteHomeEntries(originData: ['seo_noindex' => '@default']);

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    expect($field->fieldtype()->augment('@default'))->toBe(true);
});

it('preprocesses @default using origin default for synced non-text field', function () {
    [, $french] = makeTwoSiteHomeEntries(originData: ['seo_noindex' => '@default']);

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    expect($field->fieldtype()->preProcess('@default'))
        ->toBe(['source' => 'default', 'value' => true]);
});

it('augments @default using local cascade when entry explicitly sets @default', function () {
    [, $french] = makeTwoSiteHomeEntries(
        originData: ['seo_noindex' => '@default'],
        frenchData: ['seo_noindex' => '@default'],
    );

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    expect($field->fieldtype()->augment('@default'))->toBe(false);
});

it('preprocesses @default using local default when entry explicitly sets @default', function () {
    [, $french] = makeTwoSiteHomeEntries(
        originData: ['seo_noindex' => '@default'],
        frenchData: ['seo_noindex' => '@default'],
    );

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    expect($field->fieldtype()->preProcess('@default'))
        ->toBe(['source' => 'default', 'value' => false]);
});

// --- origin / sync: text fields ---
//
// Text fields (text/textarea/code) use placeholders in the UI and always
// resolve @default against the local cascade, regardless of sync state.
// Both pipelines must honor this short-circuit.

it('augments @default using local cascade for synced text field', function () {
    [, $french] = makeTwoSiteHomeEntries(originData: ['seo_description' => '@default']);

    Seo::find('collections::pages')->in('english')->set('seo_description', 'English description')->save();
    Seo::find('collections::pages')->in('french')->set('seo_description', 'French description')->save();

    $field = makeSeoField('seo_description', ['type' => 'textarea']);
    $field->setParent($french);

    expect($field->fieldtype()->augment('@default'))->toBe('French description');
});

it('preprocesses @default using local default for synced text field', function () {
    [, $french] = makeTwoSiteHomeEntries(originData: ['seo_description' => '@default']);

    Seo::find('collections::pages')->in('english')->set('seo_description', 'English description')->save();
    Seo::find('collections::pages')->in('french')->set('seo_description', 'French description')->save();

    $field = makeSeoField('seo_description', ['type' => 'textarea']);
    $field->setParent($french);

    expect($field->fieldtype()->preProcess('@default'))
        ->toBe(['source' => 'default', 'value' => 'French description']);
});

// --- origin / sync: matching cascades ---
//
// When origin and local cascades resolve to the same value, the origin-swap
// short-circuit should kick in and use the local cascade (no work saved,
// but the equality check is the cheapest path through shouldUseOriginDefault).

it('uses local cascade when origin and local resolve to the same value', function () {
    [, $french] = makeTwoSiteHomeEntries(originData: ['seo_noindex' => '@default']);

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', true)->save();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);

    expect($field->fieldtype()->augment('@default'))->toBe(true);
    expect($field->fieldtype()->preProcess('@default'))
        ->toBe(['source' => 'default', 'value' => true]);
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

it('includes null originDefaultValue when entry has no origin', function () {
    $field = makeSeoFieldWithParent('seo_noindex', ['type' => 'toggle']);
    $field->setValue($field->fieldtype()->preProcess('@default'));

    expect($field->fieldtype()->preload()['originDefaultValue'])->toBeNull();
});

it('includes origin default for synced entries', function () {
    [, $french] = makeTwoSiteHomeEntries(originData: ['seo_noindex' => '@default']);

    Seo::find('collections::pages')->in('english')->set('seo_noindex', true)->save();
    Seo::find('collections::pages')->in('french')->set('seo_noindex', false)->save();

    $field = makeSeoField('seo_noindex', ['type' => 'toggle']);
    $field->setParent($french);
    $field->setValue($field->fieldtype()->preProcess('@default'));

    $result = $field->fieldtype()->preload();

    // Vue reads both to detect a sync swap: originDefaultValue reflects the
    // origin's cascade (true) while defaultValue reflects the local cascade (false).
    expect($result['originDefaultValue'])->toBe(true)
        ->and($result['defaultValue'])->toBe(false)
        ->and($result['isTextBasedField'])->toBe(false);
});

it('flags text-based fields in preload', function () {
    $field = makeSeoFieldWithParent('seo_description', ['type' => 'textarea']);
    $field->setValue($field->fieldtype()->preProcess('@default'));

    expect($field->fieldtype()->preload()['isTextBasedField'])->toBe(true);
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
