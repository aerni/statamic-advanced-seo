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

it('passes redirects prop when redirects feature is enabled and user can manage redirects', function () {
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
        ->assertInertia(fn ($page) => $page
            ->where('redirects', null)
        );
});

it('does not share a reserved Inertia errors page prop', function () {
    config(['advanced-seo.redirects.enabled' => true, 'advanced-seo.redirects.errors.enabled' => true]);
    flushBlink();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('errors', []));
});

it('passes redirectErrors prop when redirects and error logging are enabled', function () {
    config(['advanced-seo.redirects.enabled' => true, 'advanced-seo.redirects.errors.enabled' => true]);
    flushBlink();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('redirectErrors.url', cp_route('advanced-seo.redirects.errors.index'))
            ->where('redirectErrors.icon', 'alert-warning-exclamation-mark')
        );
});

it('passes null redirectErrors prop when error logging is disabled', function () {
    config(['advanced-seo.redirects.enabled' => true, 'advanced-seo.redirects.errors.enabled' => false]);
    flushBlink();

    $this->actingAs($this->super)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('redirectErrors', null)
        );
});

it('passes null redirects prop when user cannot manage redirects', function () {
    config(['advanced-seo.redirects.enabled' => true]);
    flushBlink();

    $role = tap(Role::make('seo_editor')->addPermission(['access cp', 'edit seo defaults', 'edit pages entries', 'access default site']))->save();
    $user = tap(User::make()->assignRole('seo_editor'))->save();

    $this->actingAs($user)
        ->get(cp_route('advanced-seo.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('redirects', null)
        );
});

it('returns 200 for a redirects-only user with no seo set access', function () {
    config(['advanced-seo.redirects.enabled' => true]);
    flushBlink();

    $role = tap(Role::make('redirect_viewer')->addPermission(['access cp', 'manage redirects', 'access default site']))->save();
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
