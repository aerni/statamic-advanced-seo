<?php

use Aerni\AdvancedSeo\GraphQL\Queries\SeoSetQuery;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSetType;
use Statamic\Facades\Site;

it('has the correct name', function () {
    expect((new SeoSetQuery)->name)->toBe('seoSet');
});

it('returns the SeoSetType', function () {
    expect((new SeoSetQuery)->type()->name)->toBe(SeoSetType::NAME);
});

it('resolves by passing through the args', function () {
    $args =  ['site' => 'english'];

    $result = (new SeoSetQuery)->resolve(null, $args);

    expect($result)->toBe($args);
});
