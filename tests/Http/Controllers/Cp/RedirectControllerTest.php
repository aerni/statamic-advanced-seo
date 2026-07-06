<?php

use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    $this->super = tap(User::make()->makeSuper())->save();
});

function redirectViewer()
{
    tap(Role::make('viewer')->addPermission(['access cp', 'view redirects', 'access default site']))->save();

    return tap(User::make()->assignRole('viewer'))->save();
}

it('returns 404 on the free edition', function () {
    useFreeEdition();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.index'))
        ->assertNotFound();
});

it('shows the index to an authorized user', function () {
    $this->actingAs($this->super)->getJson(cp_route('advanced-seo.redirects.index'))->assertOk();
});

it('returns hasRedirects false when there are no redirects', function () {
    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('hasRedirects', false));
});

it('returns hasRedirects true when at least one redirect exists', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('hasRedirects', true));
});

it('allows a viewer to load the index', function () {
    $this->actingAs(redirectViewer())->getJson(cp_route('advanced-seo.redirects.index'))->assertOk();
});

it('forbids a viewer from loading the create form', function () {
    $this->actingAs(redirectViewer())->getJson(cp_route('advanced-seo.redirects.create'))->assertForbidden();
});

it('forbids a viewer from loading the edit form', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs(redirectViewer())->getJson(cp_route('advanced-seo.redirects.edit', $redirect->id()))->assertForbidden();
});

it('forbids the index without permission', function () {
    $nobody = tap(User::make())->save();
    $this->actingAs($nobody)->getJson(cp_route('advanced-seo.redirects.index'))->assertForbidden();
});

it('renders the create form as an Inertia page', function () {
    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('advanced-seo::Redirects/Create')
            ->has('blueprint')
            ->has('values')
            ->has('meta')
            ->where('enabled', true)
            ->where('submitUrl', cp_route('advanced-seo.redirects.store'))
        );
});

it('prefills the create form from source and site query parameters', function () {
    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.create').'?source=/missing&site=default')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('advanced-seo::Redirects/Create')
            ->where('values.source', '/missing')
            ->where('values.site', 'default')
        );
});

it('renders the edit form as an Inertia page for an existing redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default')->enabled(false)->preserveQueryString(false))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('advanced-seo::Redirects/Edit')
            ->has('blueprint')
            ->has('meta')
            ->where('enabled', false)
            ->where('submitUrl', cp_route('advanced-seo.redirects.update', $redirect->id()))
            ->where('values.source', '/old')
            ->where('values.destination', '/new')
            ->where('values.preserve_query_string', false)
            ->whereNot('createdAt', null)
            ->where('origin', 'Manual')
        );
});

it('includes hit data on the edit page when hit logging is enabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    tap(Redirect::make()->id('r1')->source('/old')->destination('/new')->site('default'))->save();
    Redirect::hits()->make()->redirect('r1')->count(9)->lastHitAt(1751450400)->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', 'r1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('hits.count', 9)
            ->whereNot('hits.last_hit_at', null)
        );
});

it('sends null hits on the edit page when hit logging is disabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => false]);

    tap(Redirect::make()->id('r1')->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', 'r1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('hits', null));
});

it('exposes the reset hits item action on the edit page when hit logging is enabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => true]);

    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('itemActionUrl', cp_route('advanced-seo.redirects.actions.run'))
            ->where('itemActions', fn ($actions) => collect($actions)->contains('handle', 'reset_redirect_hits'))
        );
});

it('excludes the enable and disable actions from the edit page dropdown', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('itemActions', fn ($actions) => collect($actions)
                ->pluck('handle')
                ->doesntContain(fn ($handle) => in_array($handle, ['enable_redirect', 'disable_redirect']))
            )
        );
});

it('hides the reset hits item action on the edit page when hit logging is disabled', function () {
    config(['advanced-seo.redirects.hits.enabled' => false]);

    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('itemActions', fn ($actions) => ! collect($actions)->contains('handle', 'reset_redirect_hits'))
        );
});

it('404s editing a missing redirect', function () {
    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', 'missing'))
        ->assertNotFound();
});

it('creates a redirect', function () {
    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.store'), [
            'source' => '/old',
            'destination' => '/new',
            'response_code' => 301,
            'enabled' => true,
            'site' => 'default',
        ])
        ->assertOk();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/old')->first();
    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe('/new')
        ->and($redirect->responseCode())->toBe(ResponseCode::Permanent)
        ->and($redirect->origin())->toBe(Origin::Manual);
});

it('records the error origin when created from an error', function () {
    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.store'), [
            'source' => '/old',
            'destination' => '/new',
            'response_code' => 301,
            'site' => 'default',
            'origin' => 'error',
        ])
        ->assertOk();

    expect(Redirect::query()->where('source', '/old')->first()->origin())->toBe(Origin::Error);
});

it('does not let the request set a non-error origin', function () {
    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.store'), [
            'source' => '/old',
            'destination' => '/new',
            'response_code' => 301,
            'site' => 'default',
            'origin' => 'automatic',
        ])
        ->assertOk();

    expect(Redirect::query()->where('source', '/old')->first()->origin())->toBe(Origin::Manual);
});

it('creates a disabled redirect when enabled is false', function () {
    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.store'), [
            'source' => '/disabled-route',
            'destination' => '/new',
            'response_code' => 301,
            'enabled' => false,
            'site' => 'default',
        ])
        ->assertOk();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/disabled-route')->first();
    expect($redirect)->not->toBeNull()
        ->and($redirect->enabled())->toBeFalse();
});

it('defaults enabled to true when omitted from the request', function () {
    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.store'), [
            'source' => '/omitted-enabled',
            'destination' => '/new',
            'response_code' => 301,
            'site' => 'default',
        ])
        ->assertOk();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/omitted-enabled')->first();
    expect($redirect)->not->toBeNull()
        ->and($redirect->enabled())->toBeTrue();
});

it('requires a source', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['destination' => '/new', 'response_code' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('source');
});

it('rejects a duplicate source on the same site', function () {
    Redirect::make()->source('/old')->destination('/x')->site('default')->save();

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'destination' => '/new', 'response_code' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('source');
});

it('rejects a malformed regex source', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '#^/p/(\d+$#', 'destination' => '/new', 'response_code' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('source');
});

it('rejects a malformed destination url', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'destination' => 'https//google.com', 'response_code' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('destination');
});

it('requires a destination unless the type is gone', function () {
    // 301 with destination present but null → required fires
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'response_code' => 301, 'site' => 'default', 'destination' => null])
        ->assertJsonValidationErrors('destination');

    // 410 with destination absent → sometimes skips validation
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/gone', 'response_code' => 410, 'site' => 'default'])
        ->assertValid()
        ->assertOk();

    expect(Redirect::query()->where('site', 'default')->where('source', '/gone')->first())->not->toBeNull();
});

it('drops a stale destination when the type is gone', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), [
            'source' => '/gone',
            'destination' => '/leftover',
            'response_code' => 410,
            'site' => 'default',
        ])
        ->assertOk();

    expect(Redirect::query()->where('source', '/gone')->first()->fileData()['destination'])->toBeNull();
});

it('persists the forward query string choice', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'destination' => '/new', 'response_code' => 301, 'site' => 'default', 'preserve_query_string' => false])
        ->assertOk();

    expect(Redirect::query()->where('site', 'default')->where('source', '/old')->first()->preserveQueryString())->toBeFalse();
});

it('forbids a viewer from storing a redirect', function () {
    $this->actingAs(redirectViewer())
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'destination' => '/new', 'response_code' => 301, 'site' => 'default'])
        ->assertForbidden();
});

it('updates a redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->patch(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/newer', 'response_code' => 302, 'enabled' => true, 'site' => 'default',
        ])->assertOk();

    expect(Redirect::find($redirect->id())->destination())->toBe('/newer');
});

it('toggles enabled status when updating', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default')->enabled(true))->save();

    $this->actingAs($this->super)
        ->patch(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/new', 'response_code' => 301, 'enabled' => false, 'site' => 'default',
        ])->assertOk();

    expect(Redirect::find($redirect->id())->enabled())->toBeFalse();
});

it('moves a redirect when its site changes', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->patch(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/new', 'response_code' => 301, 'enabled' => true, 'site' => 'french',
        ])->assertOk();

    expect(Redirect::query()->where('site', 'default')->where('source', '/old')->first())->toBeNull();
    expect(Redirect::query()->where('site', 'french')->where('source', '/old')->first())->not->toBeNull();
});

it('forbids a viewer from updating a redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs(redirectViewer())
        ->patchJson(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/newer', 'response_code' => 301, 'enabled' => true, 'site' => 'default',
        ])->assertForbidden();
});

it('deletes a redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->delete(cp_route('advanced-seo.redirects.destroy', $redirect->id()))
        ->assertOk();

    expect(Redirect::find($redirect->id()))->toBeNull();
});

it('forbids a viewer from deleting a redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs(redirectViewer())
        ->deleteJson(cp_route('advanced-seo.redirects.destroy', $redirect->id()))
        ->assertForbidden();
});

it('includes a non-null testUrl for an exact redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('testUrl', fn ($testUrl) => str_ends_with($testUrl, '/old'))
        );
});

it('returns a non-null testUrl for a wildcard redirect', function () {
    $redirect = tap(Redirect::make()->source('/blog/*')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('testUrl', fn ($testUrl) => str_ends_with($testUrl, '/blog/wildcard1'))
        );
});

it('returns null testUrl for a regex redirect', function () {
    $redirect = tap(Redirect::make()->source('#^/x$#')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('testUrl', null));
});
