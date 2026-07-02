<?php

use Aerni\AdvancedSeo\Rules\NonCircularRedirectDestination;
use Illuminate\Support\Facades\Validator;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
});

function circularPasses(string $destination, string $source = '/launch', string $site = 'default'): bool
{
    return Validator::make(
        ['destination' => $destination, 'source' => $source, 'site' => $site],
        ['destination' => [new NonCircularRedirectDestination]]
    )->passes();
}

it('fails when a path destination equals the source', function () {
    expect(circularPasses('/launch'))->toBeFalse();
});

it('fails when the destination only differs by case or trailing slash', function () {
    expect(circularPasses('/Launch/'))->toBeFalse();
});

it('fails when an entry destination resolves to the source url', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('launch'))->save();

    expect(circularPasses("entry::{$entry->id()}"))->toBeFalse();
});

it('fails for an absolute url on the same host with the source path', function () {
    expect(circularPasses('http://localhost/launch'))->toBeFalse();
});

it('passes when the destination is a different path', function () {
    expect(circularPasses('/somewhere-else'))->toBeTrue();
});

it('passes when an entry destination resolves elsewhere', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('other'))->save();

    expect(circularPasses("entry::{$entry->id()}"))->toBeTrue();
});

it('passes for an external url with the same path', function () {
    expect(circularPasses('https://external.test/launch'))->toBeTrue();
});

it('passes for a wildcard source', function () {
    expect(circularPasses('/launch', '/blog/*'))->toBeTrue();
});
