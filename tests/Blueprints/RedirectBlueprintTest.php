<?php

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Statamic\Facades\Site;
use Statamic\Facades\User;

beforeEach(function () {
    $this->actingAs(User::make()->makeSuper()->save());
});

it('builds with the expected fields', function () {
    $blueprint = RedirectBlueprint::definition();
    $handles = $blueprint->fields()->all()->keys()->all();

    expect($handles)->toContain('type', 'forward_query_string', 'source', 'destination', 'description');
});

it('offers the three response types', function () {
    $field = RedirectBlueprint::definition()->field('type');

    expect(array_keys($field->config()['options']))->toEqualCanonicalizing([301, 302, 410]);
});

it('hides the destination for a gone redirect', function () {
    $field = RedirectBlueprint::definition()->field('destination');

    expect($field->config())->toHaveKey('if');
});

it('enables selecting destination entries across sites', function () {
    $field = RedirectBlueprint::definition()->field('destination');

    expect($field->config()['select_across_sites'])->toBeTrue();
});

it('keeps the site as a hidden field on a multisite install', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    $field = RedirectBlueprint::definition()->field('site');

    expect($field->type())->toBe('hidden');
});
