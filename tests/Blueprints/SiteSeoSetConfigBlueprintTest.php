<?php

use Aerni\AdvancedSeo\Blueprints\SiteSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;

beforeEach(function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
        'english' => ['name' => 'English', 'url' => 'https://example.com/en', 'locale' => 'en'],
    ]);
});

it('adds the x-default site field on a multisite install', function () {
    $field = SiteSeoSetConfigBlueprint::resolve(Seo::find('site::defaults')->config())
        ->fields()
        ->all()
        ->get('hreflang_x_default');

    expect($field)->not->toBeNull()
        ->and($field->get('default'))->toBe('dutch')
        ->and($field->get('options'))->toBe([
            'dutch' => 'Dutch',
            'english' => 'English',
        ])
        ->and($field->get('validate'))->toBe(['required'])
        ->and($field->get('clearable'))->toBeFalse();
});

it('limits the x-default sites to the sites the user can access', function () {
    tap(Role::make('editor')->addPermission('access english site'))->save();
    test()->actingAs(tap(User::make()->assignRole('editor'))->save());

    $options = SiteSeoSetConfigBlueprint::resolve(Seo::find('site::defaults')->config())
        ->fields()
        ->all()
        ->get('hreflang_x_default')
        ->get('options');

    expect($options)->toBe(['english' => 'English']);
});

it('omits the x-default site field on a single site install', function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
    ]);
    flushBlink();

    $fields = SiteSeoSetConfigBlueprint::resolve(Seo::find('site::defaults')->config())->fields()->all();

    expect($fields->has('hreflang_x_default'))->toBeFalse();
});

it('omits the x-default site field on the free edition', function () {
    useFreeEdition();
    flushBlink();

    $fields = SiteSeoSetConfigBlueprint::resolve(Seo::find('site::defaults')->config())->fields()->all();

    expect($fields->has('hreflang_x_default'))->toBeFalse();
});
