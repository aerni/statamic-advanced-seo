<?php

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    config(['advanced-seo.redirects.errors.enabled' => true]);
    $this->user = User::make()->makeSuper()->save();
});

it('lists errors with a derived redirect status', function () {
    Redirect::errors()->make()->url('/handled')->site('default')->count(5)->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/unhandled')->site('default')->count(2)->lastSeenAt(Carbon::parse('2026-07-02 09:00:00')->timestamp)->save();
    Redirect::make()->source('/handled')->destination('/new')->site('default')->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index').'?sort=hits&order=desc')
        ->assertOk();

    $response->assertJsonPath('data.0.url', '/handled')
        ->assertJsonPath('data.0.hits', 5)
        ->assertJsonPath('data.0.status', 'handled')
        ->assertJsonPath('data.0.destination', '/new')
        ->assertJsonPath('data.1.url', '/unhandled')
        ->assertJsonPath('data.1.status', 'unhandled')
        ->assertJsonPath('data.1.destination', null);

    expect($response->json('data.0.redirect_url'))->not->toBeNull()
        ->and($response->json('data.1.redirect_url'))->toBeNull()
        ->and(collect($response->json('data.0.actions'))->pluck('handle'))->toContain('delete_redirect_error');
});

it('labels a gone redirect with its response code when it has no destination', function () {
    Redirect::errors()->make()->url('/gone')->site('default')->count(1)->save();
    Redirect::make()->source('/gone')->responseCode(ResponseCode::Gone)->site('default')->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk();

    $response->assertJsonPath('data.0.status', 'handled')
        ->assertJsonPath('data.0.destination', null)
        ->assertJsonPath('data.0.response_code_label', '410 (Gone)');
});

it('reports an error covered only by a disabled redirect as disabled', function () {
    Redirect::errors()->make()->url('/off')->site('default')->count(1)->save();
    Redirect::make()->source('/off')->destination('/new')->site('default')->enabled(false)->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk();

    $response->assertJsonPath('data.0.status', 'disabled');
    expect($response->json('data.0.redirect_url'))->not->toBeNull();
});

it('sorts by path ascending by default', function () {
    Redirect::errors()->make()->url('/charlie')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/alpha')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/bravo')->site('default')->count(1)->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk();

    $response->assertJsonPath('data.0.url', '/alpha')
        ->assertJsonPath('data.1.url', '/bravo')
        ->assertJsonPath('data.2.url', '/charlie');
});

it('filters by redirect status', function () {
    Redirect::errors()->make()->url('/handled')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/unhandled')->site('default')->count(1)->save();
    Redirect::make()->source('/handled')->destination('/new')->site('default')->save();

    $filters = base64_encode(json_encode(['redirect_error_status' => ['status' => 'unhandled']]));

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['filters' => $filters]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.url'))->toBe('/unhandled');
});

it('filters by site', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    Redirect::errors()->make()->url('/one')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/two')->site('fr')->count(1)->save();

    $filters = base64_encode(json_encode(['redirect_site' => ['site' => 'fr']]));

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['filters' => $filters]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.url'))->toBe('/two');
});

it('only lists errors for sites the user can access', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $role = tap(Role::make('limited')->addPermission(['access cp', 'manage redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('limited'))->save();

    Redirect::errors()->make()->url('/allowed')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/forbidden')->site('fr')->count(1)->save();

    $urls = collect($this->actingAs($user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk()
        ->json('data'))->pluck('url');

    expect($urls)->toContain('/allowed')->not->toContain('/forbidden');
});

it('shows the site column only when the user can access multiple sites', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    $singleSite = collect($this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->json('meta.columns'))->pluck('field');

    expect($singleSite)->not->toContain('site');

    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $multiSite = collect($this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->json('meta.columns'))->pluck('field');

    expect($multiSite)->toContain('site');
});

it('includes the create form payload for a user who can create redirects', function () {
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();

    $this->actingAs($this->user)
        ->get(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('advanced-seo::Redirects/Errors')
            ->where('canCreate', true)
            ->where('createUrl', cp_route('advanced-seo.redirects.store'))
            ->has('createBlueprint')
            ->has('createMeta')
            ->has('createValues.destination')
            ->has('createValues.response_code')
        );
});

it('forbids the errors index without the manage redirects permission', function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);

    tap(Role::make('viewer')->addPermission(['access cp', 'access default site']))->save();
    $viewer = tap(User::make()->assignRole('viewer'))->save();

    $this->actingAs($viewer)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertForbidden();
});

it('includes the clear payload when the user can clear errors', function () {
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();

    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    $this->actingAs($this->user)
        ->get(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canClear', true)
            ->where('clearUrl', cp_route('advanced-seo.redirects.errors.clear'))
            ->where('hasErrors', true)
        );
});

it('clears all errors', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/b')->site('default')->count(1)->save();

    $this->actingAs($this->user)
        ->post(cp_route('advanced-seo.redirects.errors.clear'))
        ->assertOk();

    expect(Redirect::errors()->query()->get())->toHaveCount(0);
});

it('only clears errors of sites the user can access', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    tap(Role::make('limited')->addPermission(['access cp', 'manage redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('limited'))->save();

    Redirect::errors()->make()->url('/allowed')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/forbidden')->site('fr')->count(1)->save();

    $this->actingAs($user)
        ->post(cp_route('advanced-seo.redirects.errors.clear'))
        ->assertOk();

    $urls = Redirect::errors()->query()->get()->map->url();
    expect($urls)->not->toContain('/allowed')->toContain('/forbidden');
});

it('forbids clearing without permission', function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);

    tap(Role::make('viewer')->addPermission(['access cp', 'access default site']))->save();
    $viewer = tap(User::make()->assignRole('viewer'))->save();

    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    $this->actingAs($viewer)
        ->post(cp_route('advanced-seo.redirects.errors.clear'))
        ->assertForbidden();

    expect(Redirect::errors()->query()->get())->toHaveCount(1);
});

it('404s clearing when error logging is disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);

    $this->actingAs($this->user)
        ->post(cp_route('advanced-seo.redirects.errors.clear'))
        ->assertNotFound();
});

it('404s when error logging is disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);

    $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertNotFound();
});
