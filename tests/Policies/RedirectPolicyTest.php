<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
});

function userWith(array $permissions)
{
    static $counter = 0;
    $handle = 'test_'.($counter++);

    $role = tap(Role::make($handle)->addPermission($permissions))->save();

    return tap(User::make()->assignRole($handle))->save();
}

it('grants viewAny to a user with view redirects and site access', function () {
    expect(userWith(['view redirects', 'access default site'])->can('viewAny', RedirectContract::class))->toBeTrue();
});

it('denies viewAny without any redirect permission', function () {
    expect(userWith([])->can('viewAny', RedirectContract::class))->toBeFalse();
});

it('denies viewAny when user lacks site access', function () {
    expect(userWith(['view redirects'])->can('viewAny', RedirectContract::class))->toBeFalse();
});

it('denies create when user lacks site access', function () {
    expect(userWith(['create redirects'])->can('create', RedirectContract::class))->toBeFalse();
});

it('maps abilities to their permissions', function () {
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');

    $editor = userWith(['edit redirects', 'access default site']);
    $creator = userWith(['create redirects', 'access default site']);
    $deleter = userWith(['delete redirects', 'access default site']);

    expect($editor->can('edit', $redirect))->toBeTrue();
    expect($editor->can('create', RedirectContract::class))->toBeFalse();
    expect($editor->can('delete', $redirect))->toBeFalse();
    expect($creator->can('create', RedirectContract::class))->toBeTrue();
    expect($creator->can('edit', $redirect))->toBeFalse();
    expect($creator->can('delete', $redirect))->toBeFalse();
    expect($deleter->can('delete', $redirect))->toBeTrue();
    expect($deleter->can('edit', $redirect))->toBeFalse();
});

it('grants super users all abilities without explicit permissions', function () {
    $user = tap(User::make()->makeSuper())->save();
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');

    expect($user->can('viewAny', RedirectContract::class))->toBeTrue();
    expect($user->can('create', RedirectContract::class))->toBeTrue();
    expect($user->can('edit', $redirect))->toBeTrue();
    expect($user->can('delete', $redirect))->toBeTrue();
});

it('denies edit when user cannot access the redirect site', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $user = userWith(['edit redirects', 'access default site']);

    $defaultRedirect = Redirect::make()->source('/old')->destination('/new')->site('default');
    $frenchRedirect = Redirect::make()->source('/vieux')->destination('/nouveau')->site('french');

    expect($user->can('edit', $defaultRedirect))->toBeTrue();
    expect($user->can('edit', $frenchRedirect))->toBeFalse();
});
