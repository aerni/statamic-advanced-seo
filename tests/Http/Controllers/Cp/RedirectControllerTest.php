<?php

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
    tap(Role::make('viewer')->addPermission('view redirects'))->save();

    return tap(User::make()->assignRole('viewer'))->save();
}

it('shows the index to an authorized user', function () {
    $this->actingAs($this->super)->get(cp_route('advanced-seo.redirects.index'))->assertOk();
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
