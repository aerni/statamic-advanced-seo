<?php

use Aerni\AdvancedSeo\GraphQL\Types\HreflangType;

it('has the correct name', function () {
    expect(HreflangType::NAME)->toBe('hreflang');
});

it('exposes all expected fields', function () {
    expect((new HreflangType)->fields())->toHaveKeys([
        'url',
        'locale',
    ]);
});

it('resolves data', function () {
    $fields = (new HreflangType)->fields();
    $data = ['url' => 'https://example.com/en', 'locale' => 'en'];

    expect($fields['url']['resolve']($data))->toBe('https://example.com/en');
    expect($fields['locale']['resolve']($data))->toBe('en');
});
