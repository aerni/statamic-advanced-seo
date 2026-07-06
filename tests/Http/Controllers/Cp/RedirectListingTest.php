<?php

use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

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
        ->toContain('preserve_query_string')
        ->toContain('site')
        ->toContain('status')
        ->toContain('origin')
        ->toContain('description')
        ->toContain('created_at');
});

it('respects the requested column visibility and order', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $columns = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['columns' => 'response_code,source']))
        ->json('meta.columns'));

    expect($columns->where('visible', true)->pluck('field')->all())->toBe(['response_code', 'source']);
});

it('exposes description and query string forwarding in the row data', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->preserveQueryString(false)->description('A note')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['description'])->toBe('A note')
        ->and($data[0]['preserve_query_string'])->toBeFalse();
});

it('exposes the origin in the row data', function () {
    Redirect::make()->source('/auto')->destination('/x')->site('default')->origin(Origin::Automatic)->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['origin'])->toBe('automatic')
        ->and($data[0]['origin_label'])->toBe('Automatic');
});

it('includes site in row data', function () {
    Redirect::make()->source('/en-page')->destination('/x')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('site')
        ->and($data[0]['site'])->toBe('default');
});

it('exposes created_at in the row data and sorts by it', function () {
    Redirect::make()->source('/older')->destination('/x')->site('default')->createdAt(1577836800)->save();
    Redirect::make()->source('/newer')->destination('/y')->site('default')->createdAt(1893456000)->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'created_at', 'order' => 'desc']))
        ->json('data');

    expect($data[0]['source'])->toBe('/newer')
        ->and($data[0]['created_at'])->not->toBeNull()
        ->and($data[1]['source'])->toBe('/older');
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
    Redirect::make()->source('/perm')->destination('/x')->site('default')->responseCode(ResponseCode::Permanent)->save();
    Redirect::make()->source('/temp')->destination('/y')->site('default')->responseCode(ResponseCode::Temporary)->save();

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

it('filters by origin', function () {
    Redirect::make()->source('/auto')->destination('/x')->site('default')->origin(Origin::Automatic)->save();
    Redirect::make()->source('/manual')->destination('/y')->site('default')->save();

    $filters = base64_encode(json_encode(['redirect_origin' => ['origin' => 'automatic']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/auto');
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

    $role = tap(Role::make('default_viewer')->addPermission(['access cp', 'view redirects', 'access default site']))->save();
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
    Redirect::make()->source('/gone')->destination(null)->site('default')->responseCode(ResponseCode::Gone)->save();

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

it('exposes a non-null absolute test_url for an exact redirect', function () {
    Redirect::make()->source('/old-page')->destination('/new-page')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('test_url')
        ->and($data[0]['test_url'])->not->toBeNull()
        ->and($data[0]['test_url'])->toStartWith('http')
        ->and($data[0]['test_url'])->toEndWith('/old-page');
});

it('exposes a non-null test_url for a wildcard redirect', function () {
    Redirect::make()->source('/old/*')->destination('/new')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('test_url')
        ->and($data[0]['test_url'])->not->toBeNull()
        ->and($data[0]['test_url'])->toStartWith('http')
        ->and($data[0]['test_url'])->toEndWith('/old/wildcard1');
});

it('exposes a null test_url for a regex redirect', function () {
    Redirect::make()->source('#^/old/(.*)$#')->destination('/new')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('test_url')
        ->and($data[0]['test_url'])->toBeNull();
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

it('includes hit data on listed redirects when hit logging is enabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    Redirect::make()->id('r1')->source('/old')->destination('/new')->site('default')->save();
    Redirect::hits()->make()->redirect('r1')->count(7)->lastHitAt(1751450400)->save();

    $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->assertJsonPath('data.0.hits', 7)
        ->assertJsonPath('data.0.last_hit_at', fn ($value) => is_string($value) && $value !== '');
});

it('defaults hits to zero and last hit to null for a redirect with no hit record', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    Redirect::make()->id('r1')->source('/old')->destination('/new')->site('default')->save();

    $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->assertJsonPath('data.0.hits', 0)
        ->assertJsonPath('data.0.last_hit_at', null);
});

it('shows the hit columns only when hit logging is enabled', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    config(['advanced-seo.redirects.hits.enabled' => true]);
    $enabled = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('meta.columns'))->pluck('field')->all();

    config(['advanced-seo.redirects.hits.enabled' => false]);
    $disabled = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('meta.columns'))->pluck('field')->all();

    expect($enabled)->toContain('hits')->toContain('last_hit_at');
    expect($disabled)->not->toContain('hits')->not->toContain('last_hit_at');
});

it('marks the native columns as sortable', function () {
    $columns = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('meta.columns'))->keyBy('field');

    expect($columns['status']['sortable'])->toBeTrue()
        ->and($columns['origin']['sortable'])->toBeTrue()
        ->and($columns['description']['sortable'])->toBeTrue()
        ->and($columns['preserve_query_string']['sortable'])->toBeTrue();
});

it('sorts by status, mapping to the enabled field', function () {
    Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true)->save();
    Redirect::make()->source('/b')->destination('/y')->site('default')->enabled(false)->save();

    $statuses = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'status', 'order' => 'asc']))
        ->json('data'))->pluck('status')->all();

    expect($statuses)->toBe([false, true]);
});

it('sorts by description', function () {
    Redirect::make()->source('/a')->destination('/x')->site('default')->description('Zebra')->save();
    Redirect::make()->source('/b')->destination('/y')->site('default')->description('Apple')->save();

    $descriptions = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'description', 'order' => 'asc']))
        ->json('data'))->pluck('description')->all();

    expect($descriptions)->toBe(['Apple', 'Zebra']);
});

it('sorts by hits, merging the separate hit store', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(5)->save();
    Redirect::hits()->make()->redirect('r2')->count(50)->save();
    // r3 has no hit record (0)

    $order = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'hits', 'order' => 'desc']))
        ->json('data'))->pluck('id')->all();

    expect($order)->toBe(['r2', 'r1', 'r3']);
});

it('sorts by last hit, merging the separate hit store', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(1)->lastHitAt(1000)->save();
    Redirect::hits()->make()->redirect('r2')->count(1)->lastHitAt(9000)->save();
    // r3 never hit

    $order = collect($this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['sort' => 'last_hit_at', 'order' => 'desc']))
        ->json('data'))->pluck('id')->all();

    expect($order)->toBe(['r2', 'r1', 'r3']);
});

it('paginates correctly when sorting by hits', function () {
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
