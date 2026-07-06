<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    config(['advanced-seo.redirects.errors.enabled' => true]);
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    $this->super = tap(User::make()->makeSuper())->save();
});

it('lists the delete action for selected errors', function () {
    $error = tap(Redirect::errors()->make()->url('/a')->site('default'))->save();

    $handles = collect($this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.errors.actions.bulk'), ['selections' => [$error->id()]])
        ->json())->pluck('handle');

    expect($handles)->toContain('delete_redirect_error');
});

it('deletes the selected errors', function () {
    $a = tap(Redirect::errors()->make()->url('/a')->site('default'))->save();
    $b = tap(Redirect::errors()->make()->url('/b')->site('default'))->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.errors.actions.run'), [
            'action' => 'delete_redirect_error',
            'selections' => [$a->id(), $b->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirect::errors()->all())->toHaveCount(0);
});

it('deletes errors for a user with the configure seo catch-all permission', function () {
    tap(Role::make('seo')->addPermission(['access cp', 'configure seo', 'access default site']))->save();
    $user = tap(User::make()->assignRole('seo'))->save();

    $error = tap(Redirect::errors()->make()->url('/a')->site('default'))->save();

    $this->actingAs($user)
        ->post(cp_route('advanced-seo.redirects.errors.actions.run'), [
            'action' => 'delete_redirect_error',
            'selections' => [$error->id()],
            'values' => [],
        ]);

    expect(Redirect::errors()->find($error->id()))->toBeNull();
});

it('does not delete errors without the manage redirects permission', function () {
    $role = tap(Role::make('viewer')->addPermission(['access cp', 'access default site']))->save();
    $user = tap(User::make()->assignRole('viewer'))->save();

    $error = tap(Redirect::errors()->make()->url('/a')->site('default'))->save();

    $this->actingAs($user)
        ->post(cp_route('advanced-seo.redirects.errors.actions.run'), [
            'action' => 'delete_redirect_error',
            'selections' => [$error->id()],
            'values' => [],
        ]);

    expect(Redirect::errors()->find($error->id()))->not->toBeNull();
});

it('does not delete errors from sites the user cannot access', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $role = tap(Role::make('deleter')->addPermission(['access cp', 'manage redirects', 'access default site']))->save();
    $user = tap(User::make()->assignRole('deleter'))->save();

    $error = tap(Redirect::errors()->make()->url('/a')->site('fr'))->save();

    $this->actingAs($user)
        ->post(cp_route('advanced-seo.redirects.errors.actions.run'), [
            'action' => 'delete_redirect_error',
            'selections' => [$error->id()],
            'values' => [],
        ]);

    expect(Redirect::errors()->find($error->id()))->not->toBeNull();
});

it('404s for error actions when error logging is disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);

    $error = tap(Redirect::errors()->make()->url('/a')->site('default'))->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.errors.actions.run'), [
            'action' => 'delete_redirect_error',
            'selections' => [$error->id()],
            'values' => [],
        ])->assertNotFound();
});
