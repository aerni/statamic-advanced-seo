<?php

use Aerni\AdvancedSeo\Contracts\RedirectError as RedirectErrorContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('saves and finds an error by url and site', function () {
    Redirect::errors()->make()->url('/missing')->site('default')->count(2)
        ->firstSeenAt(Carbon::parse('2026-07-01 09:00:00')->timestamp)
        ->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();

    $found = Redirect::errors()->findByUrl('/missing', 'default');

    expect($found)->toBeInstanceOf(RedirectErrorContract::class)
        ->and($found->url())->toBe('/missing')
        ->and($found->site())->toBe('default')
        ->and($found->count())->toBe(2);
});

it('generates an id when none is set', function () {
    $error = Redirect::errors()->make()->url('/missing')->site('default');

    expect($error->id())->not->toBeNull();
});

it('scopes findByUrl to the site', function () {
    Redirect::errors()->make()->url('/missing')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/missing')->site('fr')->count(5)->save();

    expect(Redirect::errors()->findByUrl('/missing', 'fr')->count())->toBe(5);
});

it('defaults findByUrl to the current site when no site is given', function () {
    Redirect::errors()->make()->url('/missing')->site('default')->count(3)->save();

    expect(Redirect::errors()->findByUrl('/missing')->count())->toBe(3);
});
