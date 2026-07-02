<?php

use Aerni\AdvancedSeo\ServiceProvider;
use Illuminate\Support\Collection;
use Statamic\CP\Navigation\Nav as NavInstance;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
});

function captureNavExtensions(): array
{
    $extensions = [];

    Nav::shouldReceive('extend')->andReturnUsing(function ($callback) use (&$extensions) {
        $extensions[] = $callback;
    });

    app()->make(ServiceProvider::class, ['app' => app()])->bootAddon();

    return $extensions;
}

function runNavExtensions(array $extensions): NavInstance
{
    $nav = new NavInstance;

    foreach ($extensions as $extension) {
        $extension($nav);
    }

    return $nav;
}

it('adds a Redirects nav item for a user with view redirects permission', function () {
    $role = tap(Role::make('redirect_viewer')->addPermission(['view redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('redirect_viewer'))->save();

    $this->actingAs($user);

    $extensions = captureNavExtensions();
    $nav = runNavExtensions($extensions);

    $childNames = collect($nav->items())
        ->flatMap(function ($item) {
            $c = $item->children();

            return $c instanceof Collection ? $c : collect($c ?? []);
        })
        ->map->name();

    expect($childNames->contains('Redirects'))->toBeTrue();
});

it('does not add a Redirects nav item for a user without view redirects permission', function () {
    $user = tap(User::make())->save();

    $this->actingAs($user);

    $extensions = captureNavExtensions();
    $nav = runNavExtensions($extensions);

    $childNames = collect($nav->items())
        ->flatMap(function ($item) {
            $c = $item->children();

            return $c instanceof Collection ? $c : collect($c ?? []);
        })
        ->map->name();

    expect($childNames->contains('Redirects'))->toBeFalse();
});

it('does not add a Redirects nav item when the redirects feature is disabled', function () {
    flushBlink();
    config(['advanced-seo.redirects.enabled' => false]);

    $role = tap(Role::make('redirect_viewer')->addPermission(['view redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('redirect_viewer'))->save();

    $this->actingAs($user);

    $extensions = captureNavExtensions();
    $nav = runNavExtensions($extensions);

    $childNames = collect($nav->items())
        ->flatMap(function ($item) {
            $c = $item->children();

            return $c instanceof Collection ? $c : collect($c ?? []);
        })
        ->map->name();

    expect($childNames->contains('Redirects'))->toBeFalse();
});

it('gives the SEO section nav items their icons', function () {
    $this->actingAs(tap(User::make()->makeSuper())->save());

    $extensions = captureNavExtensions();
    $nav = runNavExtensions($extensions);

    $icons = collect($nav->items())
        ->flatMap(function ($item) {
            $c = $item->children();

            return $c instanceof Collection ? $c : collect($c ?? []);
        })
        ->mapWithKeys(fn ($item) => [$item->name() => $item->icon()]);

    expect($icons->get('Site'))->toBe('utilities')
        ->and($icons->get('Redirects'))->toBe('moved');
});
