<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('bulk deletes errors for the given sites', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/b')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/c')->site('fr')->count(1)->save();

    Redirect::errors()->deleteBySites(['default']);

    $urls = Redirect::errors()->all()->map->url();

    expect($urls)->not->toContain('/a')->not->toContain('/b')->toContain('/c');
});

it('no-ops when deleteBySites receives an empty site list', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    Redirect::errors()->deleteBySites([]);

    expect(Redirect::errors()->all())->toHaveCount(1);
});

it('bulk deletes errors by id', function () {
    $a = tap(Redirect::errors()->make()->url('/a')->site('default')->count(1))->save();
    $b = tap(Redirect::errors()->make()->url('/b')->site('default')->count(1))->save();
    $c = tap(Redirect::errors()->make()->url('/c')->site('default')->count(1))->save();

    Redirect::errors()->deleteByIds([$a->id(), $c->id()]);

    $urls = Redirect::errors()->all()->map->url();

    expect($urls)->not->toContain('/a')->toContain('/b')->not->toContain('/c');
});

it('no-ops when deleteByIds receives an empty id list', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    Redirect::errors()->deleteByIds([]);

    expect(Redirect::errors()->all())->toHaveCount(1);
});

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
