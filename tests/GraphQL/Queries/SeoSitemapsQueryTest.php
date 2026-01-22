<?php

use Aerni\AdvancedSeo\GraphQL\Queries\SeoSitemapsQuery;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapsType;

it('has the correct name', function () {
    expect((new SeoSitemapsQuery)->name)->toBe('seoSitemaps');
});

it('returns the SeoSitemapsType', function () {
    expect((new SeoSitemapsQuery)->type()->name)->toBe(SeoSitemapsType::NAME);
});

it('resolves by passing through the args', function () {
    $args = ['handle' => 'pages', 'site' => 'english'];

    $result = (new SeoSitemapsQuery)->resolve(null, $args);

    expect($result)->toBe($args);
});
