<?php

use Aerni\AdvancedSeo\Fieldtypes\RedirectSourceFieldtype;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Field;

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => 'https://example.com/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr/', 'locale' => 'fr'],
    ]);
});

function preloadSource(): array
{
    $field = new Field('source', ['type' => 'redirect_source']);

    return (new RedirectSourceFieldtype)->setField($field)->preload();
}

it('preloads the default site handle', function () {
    test()->actingAs(tap(User::make()->makeSuper())->save());

    expect(preloadSource()['defaultSite'])->toBe(Site::default()->handle());
});

it('preloads whether multiple sites are enabled', function () {
    test()->actingAs(tap(User::make()->makeSuper())->save());

    expect(preloadSource()['multisite'])->toBeTrue();
});

it('preloads authorized sites keyed by handle with their names for a super user', function () {
    test()->actingAs(tap(User::make()->makeSuper())->save());

    expect(preloadSource()['sites'])->toBe([
        'default' => 'Default',
        'french' => 'French',
    ]);
});

it('preloads only authorized sites for a non-super user', function () {
    $role = tap(Role::make('editor')->addPermission('access default site'))->save();
    $user = tap(User::make()->assignRole('editor'))->save();
    test()->actingAs($user);

    expect(array_keys(preloadSource()['sites']))->toContain('default')->not->toContain('french');
});
