<?php

use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Illuminate\Support\Carbon;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesRedirects::class);

beforeEach(function () {
    config(['advanced-seo.redirects.errors.enabled' => true]);
    $this->user = User::make()->makeSuper()->save();
});

it('uses sibling error routes', function () {
    expect(cp_route('advanced-seo.redirects.errors.index'))->toEndWith('/cp/advanced-seo/errors')
        ->and(cp_route('advanced-seo.redirects.errors.clear'))->toEndWith('/cp/advanced-seo/errors/clear')
        ->and(cp_route('advanced-seo.redirects.errors.actions.run'))->toEndWith('/cp/advanced-seo/errors/actions')
        ->and(cp_route('advanced-seo.redirects.errors.actions.bulk'))->toEndWith('/cp/advanced-seo/errors/actions/list');
});

it('lists errors with a derived redirect status', function () {
    Redirect::errors()->make()->url('/enabled')->site('default')->count(5)->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/unhandled')->site('default')->count(2)->lastSeenAt(Carbon::parse('2026-07-02 09:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/low')->site('default')->count(1)->lastSeenAt(Carbon::parse('2026-07-02 08:00:00')->timestamp)->save();
    Redirect::make()->source('/enabled')->destination('/new')->site('default')->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index').'?sort=hits&order=desc')
        ->assertOk();

    $response->assertJsonPath('data.0.url', '/unhandled')
        ->assertJsonPath('data.0.hits', 2)
        ->assertJsonPath('data.0.status', 'unhandled')
        ->assertJsonPath('data.0.destination', null)
        ->assertJsonPath('data.1.url', '/low')
        ->assertJsonPath('data.1.hits', 1);

    expect(collect($response->json('data'))->pluck('url'))->not->toContain('/enabled')
        ->and($response->json('data.0.redirect_url'))->toBeNull()
        ->and(collect($response->json('data.0.actions'))->pluck('handle'))->toContain('delete_redirect_error');
});

it('does not list an error covered by a gone redirect', function () {
    Redirect::errors()->make()->url('/gone')->site('default')->count(1)->save();
    Redirect::make()->source('/gone')->responseCode(RedirectResponseCode::Gone)->site('default')->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
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

it('searches urls case-insensitively', function () {
    Redirect::errors()->make()->url('/wp-admin')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/other')->site('default')->count(1)->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['search' => 'WP-ADMIN']))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.url'))->toBe('/wp-admin');
});

it('does not apply the default sort while searching', function () {
    Redirect::errors()->make()->url('/charlie')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/alpha')->site('default')->count(1)->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['search' => 'a']))
        ->assertOk();

    $response->assertJsonPath('data.0.url', '/charlie')
        ->assertJsonPath('data.1.url', '/alpha');
});

it('paginates', function () {
    foreach (range(1, 30) as $i) {
        Redirect::errors()->make()->url("/p{$i}")->site('default')->count(1)->save();
    }

    $firstPage = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['perPage' => 10]))
        ->assertOk();

    $secondPage = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['perPage' => 10, 'page' => 2]))
        ->assertOk();

    expect($firstPage->json('data'))->toHaveCount(10)
        ->and($firstPage->json('meta.total'))->toBe(30)
        ->and($secondPage->json('data'))->toHaveCount(10)
        ->and($secondPage->json('data.0.url'))->not->toBe($firstPage->json('data.0.url'));
});

it('does not list an error after creating an enabled exact redirect', function () {
    Redirect::errors()->make()->url('/from-error')->site('default')->count(1)->save();

    $this->actingAs($this->user)
        ->post(cp_route('advanced-seo.redirects.store'), [
            'source' => '/from-error',
            'destination' => '/new',
            'response_code' => 301,
            'enabled' => true,
            'site' => 'default',
            'origin' => 'error',
        ])
        ->assertOk();

    $response = $this->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('url'))->not->toContain('/from-error');
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
            ->where('createUrl', cp_route('advanced-seo.redirects.store'))
            ->has('createBlueprint')
            ->has('createMeta')
            ->has('createValues.destination')
            ->has('createValues.response_code')
        );
});

it('makes the site of the source field read-only on the create form', function () {
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();

    $this->actingAs($this->user)
        ->get(cp_route('advanced-seo.redirects.errors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('createBlueprint', function ($blueprint) {
                $source = collect(data_get($blueprint, 'tabs.0.sections.0.fields'))->firstWhere('handle', 'source');

                return data_get($source, 'site_read_only') === true;
            })
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
        ->postJson(cp_route('advanced-seo.redirects.errors.clear'))
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
