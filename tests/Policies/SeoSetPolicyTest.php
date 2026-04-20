<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Policies\SeoSetPolicy;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    $this->policy = new SeoSetPolicy;

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
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

// --- edit() ---

it('allows edit for a site localization with configure seo permission', function () {
    $role = tap(Role::make('seo-admin')
        ->addPermission('access english site')
        ->addPermission('configure seo'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('site::defaults')->in('english');

    expect($this->policy->edit($user, $localization))->toBeTrue();
});

it('denies edit for a site localization without configure seo', function () {
    $role = tap(Role::make('editor')
        ->addPermission('access english site')
        ->addPermission('edit seo defaults'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('site::defaults')->in('english');

    expect($this->policy->edit($user, $localization))->toBeFalse();
});

it('allows edit for a collection localization with edit seo defaults and content access', function () {
    $role = tap(Role::make('editor')
        ->addPermission('access english site')
        ->addPermission('edit seo defaults')
        ->addPermission('edit pages entries'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('collections::pages')->in('english');

    expect($this->policy->edit($user, $localization))->toBeTrue();
});

it('denies edit for a collection localization without content access', function () {
    $role = tap(Role::make('seo-only')
        ->addPermission('access english site')
        ->addPermission('edit seo defaults'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('collections::pages')->in('english');

    expect($this->policy->edit($user, $localization))->toBeFalse();
});

it('denies edit for a collection localization without any seo permission', function () {
    $role = tap(Role::make('editor')
        ->addPermission('access english site')
        ->addPermission('edit pages entries'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('collections::pages')->in('english');

    expect($this->policy->edit($user, $localization))->toBeFalse();
});

it('allows edit for a taxonomy localization with edit seo defaults and content access', function () {
    $role = tap(Role::make('editor')
        ->addPermission('access english site')
        ->addPermission('edit seo defaults')
        ->addPermission('edit tags terms'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('taxonomies::tags')->in('english');

    expect($this->policy->edit($user, $localization))->toBeTrue();
});

it('denies edit when user cannot access the site', function () {
    $role = tap(Role::make('editor')
        ->addPermission('configure seo')
        ->addPermission('edit pages entries'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $localization = Seo::find('collections::pages')->in('english');

    expect($this->policy->edit($user, $localization))->toBeFalse();
});

// --- viewAny() ---

it('allows viewAny when the group has any editable localization', function () {
    $role = tap(Role::make('editor')
        ->addPermission('access english site')
        ->addPermission('edit seo defaults')
        ->addPermission('edit pages entries'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $group = Seo::groups()->first(fn (SeoSetGroup $g) => $g->type() === 'collections');

    expect($this->policy->viewAny($user, $group))->toBeTrue();
});

it('denies viewAny when no localization in the group is editable', function () {
    $role = tap(Role::make('editor'))->save();
    $user = tap(User::make()->assignRole($role))->save();

    $group = Seo::groups()->first(fn (SeoSetGroup $g) => $g->type() === 'collections');

    expect($this->policy->viewAny($user, $group))->toBeFalse();
});
