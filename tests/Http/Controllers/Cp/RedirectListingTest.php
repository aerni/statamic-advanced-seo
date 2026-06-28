<?php

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
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
    Redirects::make()->source('/old-en')->destination('/new')->site('default')->save();
    Redirects::make()->source('/old-fr')->destination('/nouveau')->site('french')->save();

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
        ->toContain('type')
        ->toContain('site')
        ->toContain('status');
});

it('includes site in row data', function () {
    Redirects::make()->source('/en-page')->destination('/x')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('site')
        ->and($data[0]['site'])->toBe('default');
});

it('searches by source and destination', function () {
    Redirects::make()->source('/alpha')->destination('/x')->site('default')->save();
    Redirects::make()->source('/beta')->destination('/y')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['search' => 'alpha']))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/alpha');
});

it('paginates', function () {
    foreach (range(1, 30) as $i) {
        Redirects::make()->source("/p{$i}")->destination('/x')->site('default')->save();
    }

    $response = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['perPage' => 10]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('meta.total'))->toBe(30);
});

it('filters by type', function () {
    Redirects::make()->source('/perm')->destination('/x')->site('default')->type(RedirectType::Permanent)->save();
    Redirects::make()->source('/temp')->destination('/y')->site('default')->type(RedirectType::Temporary)->save();

    $filters = base64_encode(json_encode(['redirect_type' => ['type' => '302']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/temp');
});

it('filters by status', function () {
    Redirects::make()->source('/enabled')->destination('/x')->site('default')->enabled(true)->save();
    Redirects::make()->source('/disabled')->destination('/y')->site('default')->enabled(false)->save();

    $filters = base64_encode(json_encode(['redirect_status' => ['enabled' => 'false']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/disabled');
});

it('filters by site', function () {
    Redirects::make()->source('/en-page')->destination('/x')->site('default')->save();
    Redirects::make()->source('/fr-page')->destination('/y')->site('french')->save();

    $filters = base64_encode(json_encode(['redirect_site' => ['site' => 'french']]));

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index', ['filters' => $filters]))
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/fr-page');
});

it('only returns redirects for sites a non-super user is authorized to view', function () {
    Redirects::make()->source('/en-page')->destination('/x')->site('default')->save();
    Redirects::make()->source('/fr-page')->destination('/y')->site('french')->save();

    $role = tap(Role::make('default_viewer')->addPermission(['access cp', 'view redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('default_viewer'))->save();

    $data = $this->actingAs($user)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->json('data');

    expect($data)->toHaveCount(1)->and($data[0]['source'])->toBe('/en-page');
});

it('resolves an entry destination to the relative entry url with destination_is_entry true', function () {
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();

    Redirects::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $siteHost = Site::get('default')->absoluteUrl();

    expect($data[0]['destination'])->toBe('/about')
        ->and($data[0]['destination_url'])->toStartWith($siteHost)
        ->and($data[0]['destination_url'])->toContain('/about')
        ->and($data[0]['destination_is_entry'])->toBeTrue();
});

it('shows a plain path destination as-is but absolutizes destination_url', function () {
    Redirects::make()->source('/old')->destination('/new-path')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $siteHost = Site::get('default')->absoluteUrl();

    expect($data[0]['destination'])->toBe('/new-path')
        ->and($data[0]['destination_url'])->toStartWith('http')
        ->and($data[0]['destination_url'])->toStartWith($siteHost)
        ->and($data[0]['destination_url'])->toEndWith('/new-path')
        ->and($data[0]['destination_url'])->not->toContain('/cp/')
        ->and($data[0]['destination_is_entry'])->toBeFalse();
});

it('falls back to the raw destination when an entry destination no longer exists', function () {
    $raw = 'entry::non-existent-id';

    Redirects::make()->source('/old')->destination($raw)->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination'])->toBe($raw)
        ->and($data[0]['destination_url'])->toBeNull()
        ->and($data[0]['destination_is_entry'])->toBeTrue();
});

it('shows a null destination and null destination_url for a 410 gone redirect', function () {
    Redirects::make()->source('/gone')->destination(null)->site('default')->type(RedirectType::Gone)->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination'])->toBeNull()
        ->and($data[0]['destination_url'])->toBeNull()
        ->and($data[0]['destination_is_entry'])->toBeFalse();
});

it('assembles destination_url correctly for a site with a path-prefix url', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de', 'locale' => 'de'],
    ]);

    Redirects::make()->source('/alt')->destination('/new')->site('default')->save();
    Redirects::make()->source('/alt-de')->destination('/new')->site('german')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $bySource = collect($data)->keyBy('source');

    expect($bySource['/alt']['destination_url'])->toBe('http://localhost/new')
        ->and($bySource['/alt-de']['destination_url'])->toBe('http://localhost/de/new');
});

it('passes an external destination through unchanged as destination_url', function () {
    Redirects::make()->source('/old')->destination('https://example.com/x')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0]['destination_url'])->toBe('https://example.com/x')
        ->and($data[0]['destination_is_entry'])->toBeFalse();
});

it('exposes a non-null absolute test_url for an exact redirect', function () {
    Redirects::make()->source('/old-page')->destination('/new-page')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('test_url')
        ->and($data[0]['test_url'])->not->toBeNull()
        ->and($data[0]['test_url'])->toStartWith('http')
        ->and($data[0]['test_url'])->toEndWith('/old-page');
});

it('exposes a null test_url for a wildcard redirect', function () {
    Redirects::make()->source('/old/*')->destination('/new')->site('default')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    expect($data[0])->toHaveKey('test_url')
        ->and($data[0]['test_url'])->toBeNull();
});

it('exposes a null test_url for a regex redirect', function () {
    Redirects::make()->source('#^/old/(.*)$#')->destination('/new')->site('default')->save();

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

    Redirects::make()->source('/old-en')->destination('new')->site('default')->save();
    Redirects::make()->source('/old-de')->destination('new')->site('german')->save();

    $data = $this->actingAs($this->super)
        ->getJson(cp_route('advanced-seo.redirects.index'))
        ->json('data');

    $bySource = collect($data)->keyBy('source');

    expect($bySource['/old-en']['destination'])->toBe('/new')
        ->and($bySource['/old-en']['destination_url'])->toBe('http://localhost/new')
        ->and($bySource['/old-de']['destination'])->toBe('/new')
        ->and($bySource['/old-de']['destination_url'])->toBe('http://localhost/de/new');
});
