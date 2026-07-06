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

    tap(Role::make($handle)->addPermission($permissions))->save();

    return tap(User::make()->assignRole($handle))->save();
}

it('grants manage to a user with the permission and site access', function () {
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');
    $user = userWith(['manage redirects', 'access default site']);

    expect($user->can('manage', RedirectContract::class))->toBeTrue()
        ->and($user->can('manage', $redirect))->toBeTrue();
});

it('grants manage to a user with the configure seo catch-all permission', function () {
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');
    $user = userWith(['configure seo', 'access default site']);

    expect($user->can('manage', RedirectContract::class))->toBeTrue()
        ->and($user->can('manage', $redirect))->toBeTrue();
});

it('denies manage without the manage redirects permission', function () {
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');
    $user = userWith(['access default site']);

    expect($user->can('manage', RedirectContract::class))->toBeFalse()
        ->and($user->can('manage', $redirect))->toBeFalse();
});

it('denies manage when the user lacks site access', function () {
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');
    $user = userWith(['manage redirects']);

    expect($user->can('manage', RedirectContract::class))->toBeFalse()
        ->and($user->can('manage', $redirect))->toBeFalse();
});

it('grants super users manage without explicit permissions', function () {
    $user = tap(User::make()->makeSuper())->save();
    $redirect = Redirect::make()->source('/old')->destination('/new')->site('default');

    expect($user->can('manage', RedirectContract::class))->toBeTrue()
        ->and($user->can('manage', $redirect))->toBeTrue();
});

it('denies manage when the user cannot access the redirect site', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $user = userWith(['manage redirects', 'access default site']);

    $defaultRedirect = Redirect::make()->source('/old')->destination('/new')->site('default');
    $frenchRedirect = Redirect::make()->source('/vieux')->destination('/nouveau')->site('french');

    expect($user->can('manage', $defaultRedirect))->toBeTrue()
        ->and($user->can('manage', $frenchRedirect))->toBeFalse();
});
