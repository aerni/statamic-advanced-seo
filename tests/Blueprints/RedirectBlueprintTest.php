<?php

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;

beforeEach(function () {
    $this->actingAs(User::make()->makeSuper()->save());
});

it('builds with the expected fields', function () {
    $blueprint = RedirectBlueprint::definition();
    $handles = $blueprint->fields()->all()->keys()->all();

    expect($handles)->toContain('source', 'destination', 'type', 'description');
});

it('offers the three response types', function () {
    $field = RedirectBlueprint::definition()->field('type');

    expect(array_keys($field->config()['options']))->toEqualCanonicalizing([301, 302, 410]);
});

it('hides the destination for a gone redirect', function () {
    $field = RedirectBlueprint::definition()->field('destination');

    expect($field->config())->toHaveKey('if');
});

it('shows all sites to a super user in the site field', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $super = tap(User::make()->makeSuper())->save();
    $this->actingAs($super);

    $options = RedirectBlueprint::definition()->field('site')->config()['options'];

    expect(array_keys($options))->toContain('default', 'french');
});

it('filters the site field to only authorized sites for a non-super user', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $role = tap(Role::make('editor')->addPermission('access default site'))->save();
    $user = tap(User::make()->assignRole('editor'))->save();
    $this->actingAs($user);

    $options = RedirectBlueprint::definition()->field('site')->config()['options'];

    expect(array_keys($options))->toContain('default')->not->toContain('french');
});
