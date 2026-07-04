<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
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
        ->and($response->json('data.1.create_redirect_url'))->toContain('source=%2Funhandled')
        ->and(collect($response->json('data.0.actions'))->pluck('handle'))->toContain('delete_redirect_error');
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

    $filters = base64_encode(json_encode(['redirect_error_site' => ['site' => 'fr']]));

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index', ['filters' => $filters]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.url'))->toBe('/two');
});

it('404s when error logging is disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);

    $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertNotFound();
});
