<?php

use Aerni\AdvancedSeo\Actions\ResolveSchema;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german'])->saveQuietly();

    $this->entry = tap(Entry::make()->collection('pages')->locale('english')->slug('about')->data(['title' => 'About']))->save();
});

it('resolves page tokens against the model', function () {
    expect(ResolveSchema::handle('{"name": "{{ title }}"}', $this->entry))
        ->toBe('{"name": "About"}');
});

it('applies modifiers', function () {
    expect(ResolveSchema::handle('{{ title | upper }}', $this->entry))->toBe('ABOUT');
});

it('resolves config tokens', function () {
    config()->set('app.name', 'Acme Inc');

    expect(ResolveSchema::handle('{{ config:app:name }}', $this->entry))->toBe('Acme Inc');
});

it('resolves global tokens under the set handle', function () {
    $set = GlobalSet::make('company')->sites(['english']);
    $set->save();
    $set->in('english')->data(['tagline' => 'We build things'])->save();

    expect(ResolveSchema::handle('{{ company:tagline }}', $this->entry))->toBe('We build things');
});

it('derives current_url from the model', function () {
    expect(ResolveSchema::handle('{{ current_url }}', $this->entry))
        ->toBe($this->entry->absoluteUrl());
});

it('does not leak variables between entries in the same collection', function () {
    $other = tap(Entry::make()->collection('pages')->locale('english')->slug('contact')->data(['title' => 'Contact']))->save();

    expect(ResolveSchema::handle('{{ title }}', $this->entry))->toBe('About');
    expect(ResolveSchema::handle('{{ title }}', $other))->toBe('Contact');
});

it('resolves against a localized entry', function () {
    $german = tap($this->entry->makeLocalization('german')->slug('ueber-uns')->data(['title' => 'Über uns']))->save();

    expect(ResolveSchema::handle('{{ title }}', $german))->toBe('Über uns');
});

it('returns the raw value when there is no content context', function () {
    expect(ResolveSchema::handle('{{ title }}', null))->toBe('{{ title }}');
});

it('returns null when the value is null', function () {
    expect(ResolveSchema::handle(null, $this->entry))->toBeNull();
});

it('returns token-free values unchanged', function () {
    expect(ResolveSchema::handle('{"@type": "Organization"}', $this->entry))
        ->toBe('{"@type": "Organization"}');
});

it('removes schema keys from the variable set', function () {
    expect(ResolveSchema::handle('a{{ json_ld }}b', $this->entry))->toBe('ab');
});
