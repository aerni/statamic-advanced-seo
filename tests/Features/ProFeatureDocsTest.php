<?php

use Aerni\AdvancedSeo\AdvancedSeo;
use Illuminate\Support\Facades\Http;

it('links the redirects feature to its docs', function () {
    $redirects = collect(AdvancedSeo::proFeatures())
        ->firstWhere('title', __('advanced-seo::messages.pro_feature_redirects'));

    expect($redirects)->toHaveKey('url', 'https://advanced-seo.michaelaerni.ch/usage/redirects');
});

it('has valid pro feature docs urls', function () {
    collect(AdvancedSeo::proFeatures())
        ->pluck('url')
        ->filter()
        ->each(fn ($url) => expect(Http::get($url)->status())->toBe(200));
});
