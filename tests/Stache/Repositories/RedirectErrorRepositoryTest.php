<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('returns the configured max records', function () {
    config(['advanced-seo.redirects.errors.max_records' => 50]);

    expect(Redirect::errors()->maxRecords())->toBe(50);
});

it('treats false max_records as no cap', function () {
    config(['advanced-seo.redirects.errors.max_records' => false]);

    expect(Redirect::errors()->maxRecords())->toBeNull();
});

it('clamps non-positive max_records to 1', function () {
    config(['advanced-seo.redirects.errors.max_records' => 0]);

    expect(Redirect::errors()->maxRecords())->toBe(1);
});

it('returns the configured purge days', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => 7]);

    expect(Redirect::errors()->purgeAfterDays())->toBe(7);
});

it('treats false purge_after_days as no age-out', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => false]);

    expect(Redirect::errors()->purgeAfterDays())->toBeNull();
});
