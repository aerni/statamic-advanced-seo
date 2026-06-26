<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Facades\Redirects;
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

it('grants view to a user with view redirects and site access', function () {
    $redirect = Redirects::make()->source('/old')->destination('/new')->site('default');

    expect(userWith(['view redirects', 'access default site'])->can('view', $redirect))->toBeTrue();
});

it('denies create when user lacks site access', function () {
    expect(userWith(['create redirects'])->can('create', RedirectContract::class))->toBeFalse();
});

it('maps abilities to their permissions', function () {
    $redirect = Redirects::make()->source('/old')->destination('/new')->site('default');

    $viewer = userWith(['view redirects', 'access default site']);
    $editor = userWith(['edit redirects', 'access default site']);
    $creator = userWith(['create redirects', 'access default site']);
    $deleter = userWith(['delete redirects', 'access default site']);

    expect($viewer->can('update', $redirect))->toBeFalse();
    expect($editor->can('update', $redirect))->toBeTrue();
    expect($creator->can('create', RedirectContract::class))->toBeTrue();
    expect($deleter->can('delete', $redirect))->toBeTrue();
    expect($editor->can('delete', $redirect))->toBeFalse();
});

it('grants super users all abilities without explicit permissions', function () {
    $user = tap(User::make()->makeSuper())->save();
    $redirect = Redirects::make()->source('/old')->destination('/new')->site('default');

    expect($user->can('viewAny', RedirectContract::class))->toBeTrue();
    expect($user->can('view', $redirect))->toBeTrue();
    expect($user->can('create', RedirectContract::class))->toBeTrue();
    expect($user->can('update', $redirect))->toBeTrue();
    expect($user->can('delete', $redirect))->toBeTrue();
});

it('denies update when user cannot access the redirect site', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $user = userWith(['edit redirects', 'access default site']);

    $defaultRedirect = Redirects::make()->source('/old')->destination('/new')->site('default');
    $frenchRedirect = Redirects::make()->source('/vieux')->destination('/nouveau')->site('french');

    expect($user->can('update', $defaultRedirect))->toBeTrue();
    expect($user->can('update', $frenchRedirect))->toBeFalse();
});
