<?php

use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\ImportError;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    $this->installSimpleExcelPackage();
    Storage::fake('local');
    $this->actingAs(tap(User::make()->makeSuper())->save());
});

function importFile(string $content, string $filename): string
{
    Storage::disk('local')->put("imports/{$filename}", $content);

    return Storage::disk('local')->path("imports/{$filename}");
}

it('creates redirects from a csv', function () {
    $result = Redirect::import(importFile("source,destination,site\n/old,/new,default\n/another,/target,default", 'import.csv'));

    expect($result->imported)->toBe(2);
    expect($result->errors)->toBe([]);

    $redirect = Redirect::query()->where('source', '/old')->where('site', 'default')->first();
    expect($redirect->destination())->toBe('/new');
    expect($redirect->responseCode()->value)->toBe(301);
    expect($redirect->enabled())->toBeTrue();
    expect($redirect->origin())->toBe(Origin::Import);
});

it('creates redirects from json with native types', function () {
    $json = json_encode([
        ['source' => '/old', 'destination' => '/new', 'response_code' => 302, 'preserve_query_string' => false, 'enabled' => false, 'description' => 'Legacy', 'site' => 'default'],
        ['source' => '/gone', 'destination' => null, 'response_code' => 410, 'site' => 'default'],
    ]);

    expect(Redirect::import(importFile($json, 'import.json'))->imported)->toBe(2);

    $redirect = Redirect::query()->where('source', '/old')->first();
    expect($redirect->responseCode()->value)->toBe(302);
    expect($redirect->preserveQueryString())->toBeFalse();
    expect($redirect->enabled())->toBeFalse();
    expect($redirect->description())->toBe('Legacy');

    $gone = Redirect::query()->where('source', '/gone')->first();
    expect($gone->responseCode()->value)->toBe(410);
});

it('creates a gone redirect from a csv with an empty destination', function () {
    $result = Redirect::import(importFile("source,destination,site,response_code\n/gone,,default,410", 'import.csv'));

    expect($result->imported)->toBe(1);
    expect($result->errors)->toBe([]);

    $redirect = Redirect::query()->where('source', '/gone')->first();
    expect($redirect->responseCode()->value)->toBe(410);
    expect($redirect->destination())->toBeNull();
});

it('updates an existing redirect matched by source and site', function () {
    Redirect::make()->id('existing')->source('/old')->destination('/original')->site('default')->origin(Origin::Manual)->save();

    $result = Redirect::import(importFile("source,destination,site\n/old,/updated,default", 'import.csv'));

    expect($result->imported)->toBe(1);

    $redirect = Redirect::find('existing');
    expect($redirect->destination())->toBe('/updated');
    expect($redirect->origin())->toBe(Origin::Import);
});

it('overwrites the full redirect, resetting absent optional columns to their defaults', function () {
    Redirect::make()->id('existing')->source('/old')->destination('/original')->responseCode(ResponseCode::Temporary)->preserveQueryString(false)->enabled(false)->description('Legacy')->site('default')->save();

    Redirect::import(importFile("source,destination,site\n/old,/updated,default", 'import.csv'));

    $redirect = Redirect::find('existing');
    expect($redirect->destination())->toBe('/updated');
    expect($redirect->responseCode()->value)->toBe(301);
    expect($redirect->preserveQueryString())->toBeTrue();
    expect($redirect->enabled())->toBeTrue();
    expect($redirect->description())->toBeNull();
});

it('falls back to the selected site on a single-site install', function () {
    config(['statamic.system.multisite' => false]);

    $result = Redirect::import(importFile("source,destination\n/old,/new", 'import.csv'));

    expect($result->imported)->toBe(1);
    expect(Redirect::query()->where('source', '/old')->first()->site())->toBe('default');
});

it('imports nothing when any row is invalid', function () {
    $result = Redirect::import(importFile("source,destination,site\n,/new,default\nnot-a-path,/new,default\n/valid,/target,default", 'import.csv'));

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(2);
    expect($result->errors[0])->toBeInstanceOf(ImportError::class);
    expect($result->errors[0]->row)->toBe(1);
    expect($result->successful())->toBeFalse();
    expect(Redirect::query()->count())->toBe(0);
});

it('errors a row with a blank site on multi-site', function () {
    $result = Redirect::import(importFile("source,destination,site\n/old,/new,\n/other,/target,default", 'import.csv'));

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect(Redirect::query()->count())->toBe(0);
});

it('errors a row with an unknown site', function () {
    $result = Redirect::import(importFile("source,destination,site\n/old,/new,nope\n/other,/target,default", 'import.csv'));

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect(Redirect::query()->count())->toBe(0);
});

it('errors json objects with no site on multi-site', function () {
    $json = json_encode([
        ['source' => '/old', 'destination' => '/new'],
        ['source' => '/other', 'destination' => '/target'],
    ]);

    $result = Redirect::import(importFile($json, 'import.json'));

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(2);
    expect(Redirect::query()->count())->toBe(0);
});

it('imports nothing when a row has an invalid response code', function () {
    $result = Redirect::import(importFile("source,destination,site,response_code\n/old,/new,default,999\n/other,/target,default,301", 'import.csv'));

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect(Redirect::query()->count())->toBe(0);
});

it('imports nothing when the file has a duplicate source', function () {
    $result = Redirect::import(importFile("source,destination,site\n/old,/new,default\n/old,/other,default", 'import.csv'));

    expect($result->imported)->toBe(0);
    expect($result->errors)->toHaveCount(1);
    expect(Redirect::query()->count())->toBe(0);
});

it('throws naming the missing required csv columns', function () {
    try {
        Redirect::import(importFile("url,target\n/old,/new", 'import.csv'));
        $this->fail('Import did not throw.');
    } catch (ValidationException $e) {
        expect($e->errors()['file'][0])->toContain('source')->toContain('destination');
    }
});

it('throws naming the missing site column on multi-site', function () {
    try {
        Redirect::import(importFile("source,destination\n/old,/new", 'import.csv'));
        $this->fail('Import did not throw.');
    } catch (ValidationException $e) {
        expect($e->errors()['file'][0])->toContain('site');
    }
});

it('throws when the json is malformed', function () {
    expect(fn () => Redirect::import(importFile('{"not":"a list"}', 'import.json')))
        ->toThrow(ValidationException::class);
});

it('throws when the json contains a non-object row', function () {
    expect(fn () => Redirect::import(importFile('[{"source":"/a","destination":"/b"},"garbage"]', 'import.json')))
        ->toThrow(ValidationException::class);
});
