<?php

use Aerni\AdvancedSeo\Contracts\RedirectError as RedirectErrorContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;

uses(UseEloquentDriver::class);

it('saves and finds an error via eloquent', function () {
    Redirect::errors()->make()->url('/missing')->site('default')->count(2)->save();

    $found = Redirect::errors()->findByUrl('/missing', 'default');

    expect($found)->toBeInstanceOf(RedirectErrorContract::class)
        ->and($found->url())->toBe('/missing')
        ->and($found->count())->toBe(2);
});

it('lists and deletes errors via eloquent', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();
    $b = Redirect::errors()->make()->url('/b')->site('default')->count(1);
    $b->save();

    expect(Redirect::errors()->all())->toHaveCount(2);

    $b->delete();

    expect(Redirect::errors()->all())->toHaveCount(1);
});
