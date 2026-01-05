<?php

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

it('can find seo defaults', function () {
    expect(Seo::find('site::general'))->toBeInstanceOf(SeoSet::class);
});

it('can get all seo defaults', function () {
    $sets = Seo::all();

    expect($sets)->toBeInstanceOf(Collection::class);
});
