<?php

use Aerni\AdvancedSeo\Rules\PublishedRedirectDestination;
use Illuminate\Support\Facades\Validator;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
});

function destinationPasses(string $value): bool
{
    return Validator::make(
        ['destination' => $value],
        ['destination' => [new PublishedRedirectDestination]]
    )->passes();
}

it('passes when the destination is a plain url', function () {
    expect(destinationPasses('/somewhere'))->toBeTrue();
});

it('passes when the destination entry is published', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('about')->published(true))->save();

    expect(destinationPasses("entry::{$entry->id()}"))->toBeTrue();
});

it('fails when the destination entry is unpublished', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('draft')->published(false))->save();

    expect(destinationPasses("entry::{$entry->id()}"))->toBeFalse();
});

it('passes when the destination entry does not exist', function () {
    expect(destinationPasses('entry::nonexistent-id'))->toBeTrue();
});
