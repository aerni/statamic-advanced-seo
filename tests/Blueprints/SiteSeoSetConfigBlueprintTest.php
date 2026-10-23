<?php

use Aerni\AdvancedSeo\Blueprints\SiteSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Site;

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
        ->and($field->type())->toBe('hreflang_x_default')
        ->and($field->get('default'))->toBe('dutch')
        ->and($field->get('validate'))->toBe(['required']);
});

it('omits the x-default site field on a single site install', function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
    ]);
    flushBlink();

    $fields = SiteSeoSetConfigBlueprint::resolve(Seo::find('site::defaults')->config())->fields()->all();

    expect($fields->has('hreflang_x_default'))->toBeFalse();
});

it('adds the x-default site field on the free edition', function () {
    useFreeEdition();
    flushBlink();

    $fields = SiteSeoSetConfigBlueprint::resolve(Seo::find('site::defaults')->config())->fields()->all();

    expect($fields->has('hreflang_x_default'))->toBeTrue();
});
