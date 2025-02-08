<?php

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

it('can find seo defaults', function () {
    expect(Seo::find('site', 'general'))->toBeInstanceOf(SeoDefaultSet::class);
});

it('can get all seo defaults', function () {
    $defaults = Seo::all();

    expect($defaults)->toBeInstanceOf(Collection::class);
    expect($defaults)->toHaveKey('collections');
    expect($defaults)->toHaveKey('taxonomies');
    expect($defaults)->toHaveKey('site');
});
