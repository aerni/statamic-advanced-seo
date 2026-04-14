<?php

use Aerni\AdvancedSeo\Policies\SeoSetPolicy;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    $this->policy = new SeoSetPolicy;
});

it('grants super users configure access', function (string $type, string $handle) {
    $user = tap(User::make()->makeSuper())->save();
    $seoSet = new SeoSet($type, $handle, 'Title', 'icon');

    expect($this->policy->before($user))->toBeTrue();
})->with([
    'site' => ['site', 'general'],
    'collections' => ['collections', 'pages'],
    'taxonomies' => ['taxonomies', 'tags'],
]);

it('allows configure for site SeoSets with configure seo permission', function () {
    $role = tap(Role::make('seo-admin')->addPermission('configure seo'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('site', 'general', 'General', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeTrue();
});

it('denies configure for site SeoSets without configure seo permission', function () {
    $role = tap(Role::make('editor'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('site', 'general', 'General', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeFalse();
});

it('allows configure for collection SeoSets with configure seo and configure collections permissions', function () {
    $role = tap(Role::make('seo-admin')->addPermission('configure seo')->addPermission('configure collections'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('collections', 'pages', 'Pages', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeTrue();
});

it('allows configure for collection SeoSets with configure seo and edit entries permissions', function () {
    $role = tap(Role::make('editor')->addPermission('configure seo')->addPermission('edit pages entries'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('collections', 'pages', 'Pages', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeTrue();
});

it('denies configure for collection SeoSets with configure seo but no content access', function () {
    $role = tap(Role::make('seo-only')->addPermission('configure seo'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('collections', 'pages', 'Pages', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeFalse();
});

it('denies configure for collection SeoSets without configure seo permission', function () {
    $role = tap(Role::make('admin')->addPermission('configure collections'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('collections', 'pages', 'Pages', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeFalse();
});

it('allows configure for taxonomy SeoSets with configure seo and configure taxonomies permissions', function () {
    $role = tap(Role::make('seo-admin')->addPermission('configure seo')->addPermission('configure taxonomies'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('taxonomies', 'tags', 'Tags', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeTrue();
});

it('allows configure for taxonomy SeoSets with configure seo and edit terms permissions', function () {
    $role = tap(Role::make('editor')->addPermission('configure seo')->addPermission('edit tags terms'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('taxonomies', 'tags', 'Tags', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeTrue();
});

it('denies configure for taxonomy SeoSets with configure seo but no content access', function () {
    $role = tap(Role::make('seo-only')->addPermission('configure seo'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('taxonomies', 'tags', 'Tags', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeFalse();
});

it('denies configure for taxonomy SeoSets without configure seo permission', function () {
    $role = tap(Role::make('admin')->addPermission('configure taxonomies'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $seoSet = new SeoSet('taxonomies', 'tags', 'Tags', 'icon');

    expect($this->policy->configure($user, $seoSet))->toBeFalse();
});
