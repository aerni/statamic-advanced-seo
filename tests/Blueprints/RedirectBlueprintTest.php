<?php

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Statamic\Facades\User;

beforeEach(function () {
    $this->actingAs(User::make()->makeSuper()->save());
});

it('builds with the expected fields', function () {
    $blueprint = RedirectBlueprint::definition();
    $handles = $blueprint->fields()->all()->keys()->all();

    expect($handles)->toContain('source', 'destination', 'type', 'enabled', 'description');
});

it('offers the three response types', function () {
    $field = RedirectBlueprint::definition()->field('type');

    expect(array_keys($field->config()['options']))->toEqualCanonicalizing([301, 302, 410]);
});

it('hides the destination for a gone redirect', function () {
    $field = RedirectBlueprint::definition()->field('destination');

    expect($field->config())->toHaveKey('if');
});
