<?php

use Aerni\AdvancedSeo\GraphQL\Types\SitemapAlternatesType;

it('has the correct name', function () {
    expect(SitemapAlternatesType::NAME)->toBe('sitemapAlternates');
});

it('exposes all expected fields', function () {
    expect((new SitemapAlternatesType)->fields())->toHaveKeys([
        'href',
        'hreflang',
    ]);
});

it('resolves data', function () {
    $fields = (new SitemapAlternatesType)->fields();
    $alternate = ['href' => 'https://example.com/de', 'hreflang' => 'de'];

    expect($fields['href']['resolve']($alternate))->toBe('https://example.com/de');
    expect($fields['hreflang']['resolve']($alternate))->toBe('de');
});
