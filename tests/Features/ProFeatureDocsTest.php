<?php

use Aerni\AdvancedSeo\AdvancedSeo;
use Illuminate\Support\Facades\Http;

it('has valid pro feature docs urls', function () {
    collect(AdvancedSeo::proFeatures())
        ->pluck('url')
        ->filter()
        ->each(fn ($url) => expect(Http::get($url)->status())->toBe(200));
});
