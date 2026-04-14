<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Facades\Gate;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();
});

it('denies a super user when the SeoSet is not editable', function () {
    $user = tap(User::make()->makeSuper())->save();
    Seo::find('collections::pages')->config()->editable(false)->save();

    expect(Gate::forUser($user)->allows('seo.edit-content', Seo::find('collections::pages')))->toBeFalse();
});

it('denies a user with seo permissions when the SeoSet is not editable', function () {
    $role = tap(Role::make('seo-editor')->addPermission('edit seo content'))->save();
    $user = tap(User::make()->assignRole($role))->save();
    Seo::find('collections::pages')->config()->editable(false)->save();

    expect(Gate::forUser($user)->allows('seo.edit-content', Seo::find('collections::pages')))->toBeFalse();
});

it('allows a super user when the SeoSet is editable', function () {
    $user = tap(User::make()->makeSuper())->save();

    expect(Gate::forUser($user)->allows('seo.edit-content', Seo::find('collections::pages')))->toBeTrue();
});

it('allows a user with seo permissions when the SeoSet is editable', function () {
    $role = tap(Role::make('seo-editor')->addPermission('edit seo content'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    expect(Gate::forUser($user)->allows('seo.edit-content', Seo::find('collections::pages')))->toBeTrue();
});

it('denies a user without seo permissions when the SeoSet is editable', function () {
    $user = tap(User::make())->save();

    expect(Gate::forUser($user)->allows('seo.edit-content', Seo::find('collections::pages')))->toBeFalse();
});

it('allows any user when the edition is free, regardless of the SeoSet editable state', function () {
    useFreeEdition();

    $user = tap(User::make())->save();
    Seo::find('collections::pages')->config()->editable(false)->save();

    expect(Gate::forUser($user)->allows('seo.edit-content', Seo::find('collections::pages')))->toBeTrue();
});
