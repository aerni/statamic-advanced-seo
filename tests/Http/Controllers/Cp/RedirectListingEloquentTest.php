<?php

use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;

uses(UseEloquentDriver::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => 'http://localhost/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'http://localhost/fr/', 'locale' => 'fr'],
    ]);
    $this->super = tap(User::make()->makeSuper())->save();
});

it('returns all authorized sites redirects as json for a super user', function () {
    Redirect::make()->source('/old-en')->destination('/new')->site('default')->save();
    Redirect::make()->source('/old-fr')->destination('/nouveau')->site('french')->save();

    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['columns'], 'links']);

    expect($response->json('meta.total'))->toBe(2);
});

it('returns correct columns in meta', function () {
    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk();

    $columnFields = collect($response->json('meta.columns'))->pluck('field')->all();

    expect($columnFields)->toContain('source')
        ->toContain('destination')
        ->toContain('response_code')
        ->toContain('site')
        ->toContain('status');
});

it('includes site in row data', function () {
    Redirect::make()->source('/en-page')->destination('/x')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('site')
        ->and($data[0]['site'])->toBe('default');
});

it('searches by source and destination', function () {
    Redirect::make()->source('/alpha')->destination('/x')->site('default')->save();
    Redirect::make()->source('/beta')->destination('/y')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['search' => 'alpha']))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/alpha');
});

it('paginates', function () {
    foreach (range(1, 30) as $i) {
        Redirect::make()->source("/p{$i}")->destination('/x')->site('default')->save();
    }

    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['perPage' => 10]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('meta.total'))->toBe(30);
});

it('filters by type', function () {
    Redirect::make()->source('/perm')->destination('/x')->site('default')->responseCode(RedirectResponseCode::Permanent)->save();
    Redirect::make()->source('/temp')->destination('/y')->site('default')->responseCode(RedirectResponseCode::Temporary)->save();

    $filters = base64_encode(json_encode(['redirect_response_code' => ['response_code' => '302']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/temp');
});

it('filters by status', function () {
    Redirect::make()->source('/enabled')->destination('/x')->site('default')->enabled(true)->save();
    Redirect::make()->source('/disabled')->destination('/y')->site('default')->enabled(false)->save();

    $filters = base64_encode(json_encode(['redirect_status' => ['enabled' => 'false']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/disabled');
});

it('filters by site', function () {
    Redirect::make()->source('/en-page')->destination('/x')->site('default')->save();
    Redirect::make()->source('/fr-page')->destination('/y')->site('french')->save();

    $filters = base64_encode(json_encode(['redirect_site' => ['site' => 'french']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/fr-page');
});

it('only returns redirects for sites a non-super user is authorized to view', function () {
    Redirect::make()->source('/en-page')->destination('/x')->site('default')->save();
    Redirect::make()->source('/fr-page')->destination('/y')->site('french')->save();

    $role = tap(Role::make('default_viewer')->addPermission(['access cp', 'manage redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('default_viewer'))->save();

    $data = $this->actingAs($user)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/en-page');
});

it('shows an entry destination as an absolute url with destination_is_entry true', function () {
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();

    Redirect::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination'])->toBe($data[0]['destination_url'])
        ->and($data[0]['destination_url'])->toStartWith('http')
        ->and($data[0]['destination_url'])->toEndWith('/about')
        ->and($data[0]['destination_is_entry'])->toBeTrue();
});

it('shows a cross-site entry destination using the other site url', function () {
    Collection::make('pages')->routes('/{slug}')->sites(['default', 'french'])->saveQuietly();

    $entry = tap(Entry::make()->collection('pages')->locale('french')->slug('accueil'))->save();

    Redirect::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination'])->toBe($data[0]['destination_url'])
        ->and($data[0]['destination_url'])->toContain('/fr/accueil')
        ->and($data[0]['destination_is_entry'])->toBeTrue();
});

it('shows a plain path destination as-is but absolutizes destination_url', function () {
    Redirect::make()->source('/old')->destination('/new-path')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $siteHost = Site::get('default')->absoluteUrl();

    expect($data[0]['destination'])->toBe($data[0]['destination_url'])
        ->and($data[0]['destination_url'])->toStartWith('http')
        ->and($data[0]['destination_url'])->toStartWith($siteHost)
        ->and($data[0]['destination_url'])->toEndWith('/new-path')
        ->and($data[0]['destination_url'])->not->toContain('/cp/')
        ->and($data[0]['destination_is_entry'])->toBeFalse();
});

it('falls back to the raw destination when an entry destination no longer exists', function () {
    $raw = 'entry::non-existent-id';

    Redirect::make()->source('/old')->destination($raw)->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination'])->toBe($raw)
        ->and($data[0]['destination_url'])->toBeNull()
        ->and($data[0]['destination_is_entry'])->toBeTrue();
});

it('shows a null destination and null destination_url for a 410 gone redirect', function () {
    Redirect::make()->source('/gone')->destination(null)->site('default')->responseCode(RedirectResponseCode::Gone)->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination'])->toBeNull()
        ->and($data[0]['destination_url'])->toBeNull()
        ->and($data[0]['destination_is_entry'])->toBeFalse()
        ->and($data[0]['test_url'])->toBeNull();
});

it('assembles destination_url correctly for a site with a path-prefix url', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de', 'locale' => 'de'],
    ]);

    Redirect::make()->source('/alt')->destination('/new')->site('default')->save();
    Redirect::make()->source('/alt-de')->destination('/new')->site('german')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $bySource = collect($data)->keyBy('source');

    expect($bySource['/alt']['destination_url'])->toBe('http://localhost/new')
        ->and($bySource['/alt-de']['destination_url'])->toBe('http://localhost/de/new');
});

it('passes an external destination through unchanged as destination_url', function () {
    Redirect::make()->source('/old')->destination('https://example.com/x')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination_url'])->toBe('https://example.com/x')
        ->and($data[0]['destination_is_entry'])->toBeFalse();
});

it('treats a slashless destination as a site-relative path', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de', 'locale' => 'de'],
    ]);

    Redirect::make()->source('/old-en')->destination('new')->site('default')->save();
    Redirect::make()->source('/old-de')->destination('new')->site('german')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $bySource = collect($data)->keyBy('source');

    expect($bySource['/old-en']['destination'])->toBe('http://localhost/new')
        ->and($bySource['/old-en']['destination_url'])->toBe('http://localhost/new')
        ->and($bySource['/old-de']['destination'])->toBe('http://localhost/de/new')
        ->and($bySource['/old-de']['destination_url'])->toBe('http://localhost/de/new');
});

it('sorts by status, mapping to the enabled field, via eloquent', function () {
    Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true)->save();
    Redirect::make()->source('/b')->destination('/y')->site('default')->enabled(false)->save();

    $statuses = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'status', 'order' => 'asc']))
        ->json('data'))->pluck('status')->all();

    expect($statuses)->toBe([false, true]);
});

it('sorts by hits, merging the separate hit store, via eloquent', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(5)->save();
    Redirect::hits()->make()->redirect('r2')->count(50)->save();

    $order = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'hits', 'order' => 'desc']))
        ->json('data'))->pluck('id')->all();

    expect($order)->toBe(['r2', 'r1', 'r3']);
});

it('paginates correctly when sorting by hits via eloquent', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    foreach (range(1, 5) as $i) {
        Redirect::make()->id("r{$i}")->source("/{$i}")->destination('/x')->site('default')->save();
        Redirect::hits()->make()->redirect("r{$i}")->count($i)->save();
    }

    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'hits', 'order' => 'desc', 'perPage' => 2]))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe(['r5', 'r4'])
        ->and($response->json('meta.total'))->toBe(5);
});
