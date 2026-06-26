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

it('grants viewAny to a user with view redirects', function () {
    expect(userWith(['view redirects'])->can('viewAny', RedirectContract::class))->toBeTrue();
});

it('denies viewAny without any redirect permission', function () {
    expect(userWith([])->can('viewAny', RedirectContract::class))->toBeFalse();
});

it('maps abilities to their permissions', function () {
    $redirect = Redirects::make()->source('/old')->destination('/new')->site('default');

    $viewer = userWith(['view redirects']);
    $editor = userWith(['edit redirects']);
    $creator = userWith(['create redirects']);
    $deleter = userWith(['delete redirects']);

    expect($viewer->can('update', $redirect))->toBeFalse();
    expect($editor->can('update', $redirect))->toBeTrue();
    expect($creator->can('create', RedirectContract::class))->toBeTrue();
    expect($deleter->can('delete', $redirect))->toBeTrue();
    expect($editor->can('delete', $redirect))->toBeFalse();
});
