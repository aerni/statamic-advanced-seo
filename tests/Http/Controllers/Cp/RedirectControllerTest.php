<?php

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
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
    Redirects::make()->source('/old')->destination('/new')->site('default')->save();

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
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs(redirectViewer())->getJson(cp_route('advanced-seo.redirects.edit', $redirect->id()))->assertForbidden();
});

it('forbids the index without permission', function () {
    $nobody = tap(User::make())->save();
    $this->actingAs($nobody)->getJson(cp_route('advanced-seo.redirects.index'))->assertForbidden();
});

it('renders the create form', function () {
    $this->actingAs($this->super)->get(cp_route('advanced-seo.redirects.create'))->assertOk();
});

it('renders the edit form for an existing redirect', function () {
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.redirects.edit', $redirect->id()))
        ->assertOk();
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
            'type' => 301,
            'enabled' => true,
            'site' => 'default',
        ])
        ->assertOk();

    $redirect = Redirects::query()->where('site', 'default')->where('source', '/old')->first();
    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe('/new')
        ->and($redirect->type())->toBe(RedirectType::Permanent);
});

it('requires a source', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['destination' => '/new', 'type' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('source');
});

it('rejects a duplicate source on the same site', function () {
    Redirects::make()->source('/old')->destination('/x')->site('default')->save();

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'destination' => '/new', 'type' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('source');
});

it('rejects a malformed regex source', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '#^/p/(\d+$#', 'destination' => '/new', 'type' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('source');
});

it('requires a destination unless the type is gone', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'type' => 301, 'site' => 'default'])
        ->assertJsonValidationErrors('destination');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/gone', 'type' => 410, 'site' => 'default'])
        ->assertValid()
        ->assertOk();

    expect(Redirects::query()->where('site', 'default')->where('source', '/gone')->first())->not->toBeNull();
});

it('forbids a viewer from storing a redirect', function () {
    $this->actingAs(redirectViewer())
        ->postJson(cp_route('advanced-seo.redirects.store'), ['source' => '/old', 'destination' => '/new', 'type' => 301, 'site' => 'default'])
        ->assertForbidden();
});

it('updates a redirect', function () {
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->patch(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/newer', 'type' => 302, 'enabled' => true, 'site' => 'default',
        ])->assertOk();

    expect(Redirects::find($redirect->id())->destination())->toBe('/newer');
});

it('moves a redirect when its site changes', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->patch(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/new', 'type' => 301, 'enabled' => true, 'site' => 'french',
        ])->assertOk();

    expect(Redirects::query()->where('site', 'default')->where('source', '/old')->first())->toBeNull();
    expect(Redirects::query()->where('site', 'french')->where('source', '/old')->first())->not->toBeNull();
});

it('forbids a viewer from updating a redirect', function () {
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs(redirectViewer())
        ->patchJson(cp_route('advanced-seo.redirects.update', $redirect->id()), [
            'source' => '/old', 'destination' => '/newer', 'type' => 301, 'enabled' => true, 'site' => 'default',
        ])->assertForbidden();
});

it('deletes a redirect', function () {
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs($this->super)
        ->delete(cp_route('advanced-seo.redirects.destroy', $redirect->id()))
        ->assertOk();

    expect(Redirects::find($redirect->id()))->toBeNull();
});

it('forbids a viewer from deleting a redirect', function () {
    $redirect = tap(Redirects::make()->source('/old')->destination('/new')->site('default'))->save();

    $this->actingAs(redirectViewer())
        ->deleteJson(cp_route('advanced-seo.redirects.destroy', $redirect->id()))
        ->assertForbidden();
});
