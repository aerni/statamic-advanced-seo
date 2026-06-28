<?php

use Aerni\AdvancedSeo\Fieldtypes\RedirectSourceFieldtype;
use Statamic\Facades\Site;
use Statamic\Fields\Field;

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => 'https://example.com/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr/', 'locale' => 'fr'],
    ]);
});

it('preloads a sites map keyed by handle', function () {
    $field = new Field('source', ['type' => 'redirect_source']);
    $fieldtype = (new RedirectSourceFieldtype)->setField($field);

    $result = $fieldtype->preload();

    expect($result)->toHaveKey('sites')
        ->and($result['sites'])->not->toBeEmpty()
        ->and($result['sites'])->toHaveKey('default')
        ->and($result['sites'])->toHaveKey('french');
});

it('strips trailing slash from site urls in the preloaded map', function () {
    $field = new Field('source', ['type' => 'redirect_source']);
    $fieldtype = (new RedirectSourceFieldtype)->setField($field);

    $result = $fieldtype->preload();

    foreach ($result['sites'] as $url) {
        expect($url)->not->toEndWith('/');
    }
});
