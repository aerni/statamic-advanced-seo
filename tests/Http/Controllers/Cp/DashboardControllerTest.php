<?php

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

it('shows the dashboard to a super user', function () {
    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk();
});

it('passes groups to the dashboard', function () {
    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('groups'));
});

it('passes redirects prop when redirects feature is enabled and user can view redirects', function () {
    config(['advanced-seo.redirects.enabled' => true]);
    flushBlink();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('redirects.url', cp_route('advanced-seo.redirects.index'))
            ->where('redirects.icon', 'moved')
        );
});

it('passes null redirects prop when redirects feature is disabled', function () {
    config(['advanced-seo.redirects.enabled' => false]);
    flushBlink();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('redirects', null));
});

it('passes null redirects prop when user cannot view redirects', function () {
    config(['advanced-seo.redirects.enabled' => true]);
    flushBlink();

    $role = tap(Role::make('seo_editor')->addPermission(['access cp', 'configure seo', 'access default site']))->save();
    $user = tap(User::make()->assignRole('seo_editor'))->save();

    $this->actingAs($user)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('redirects', null));
});

it('returns 200 for a redirects-only user with no seo set access', function () {
    config(['advanced-seo.redirects.enabled' => true]);
    flushBlink();

    $role = tap(Role::make('redirect_viewer')->addPermission(['access cp', 'view redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('redirect_viewer'))->save();

    $this->actingAs($user)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('redirects.url', cp_route('advanced-seo.redirects.index'))
            ->where('redirects.icon', 'moved')
        );
});

it('returns 404 when user has no access to groups and redirects feature is disabled', function () {
    config(['advanced-seo.redirects.enabled' => false]);
    flushBlink();

    $role = tap(Role::make('cp_only')->addPermission(['access cp', 'access default site']))->save();
    $user = tap(User::make()->assignRole('cp_only'))->save();

    $this->actingAs($user)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertNotFound();
});
