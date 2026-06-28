<?php

use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Rules\UniqueRedirectSource;
use Illuminate\Support\Facades\Validator;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(fn () => Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]));

function uniquePasses(string $source, ?string $site, ?string $exceptId = null): bool
{
    return Validator::make(
        ['source' => $source],
        ['source' => [new UniqueRedirectSource($site, $exceptId)]]
    )->passes();
}

it('passes when the source is unused on the site', function () {
    expect(uniquePasses('/old', 'default'))->toBeTrue();
});

it('fails when the source already exists on the site', function () {
    Redirects::make()->source('/old')->destination('/new')->site('default')->save();
    expect(uniquePasses('/old', 'default'))->toBeFalse();
});

it('ignores the excepted redirect (its own id) on update', function () {
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();
    expect(uniquePasses('/old', 'default', $redirect->id()))->toBeTrue();
});

it('matches case-insensitively (sources are stored lowercased)', function () {
    Redirects::make()->source('/old')->destination('/new')->site('default')->save();
    expect(uniquePasses('/OLD', 'default'))->toBeFalse();
});

it('falls back to the default site when site is null', function () {
    Redirects::make()->source('/old')->destination('/new')->site('default')->save();
    expect(uniquePasses('/old', null))->toBeFalse();
});
