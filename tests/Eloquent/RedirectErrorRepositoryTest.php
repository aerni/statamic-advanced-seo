<?php

use Aerni\AdvancedSeo\Contracts\RedirectError as RedirectErrorContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Site;

uses(UseEloquentDriver::class);

it('bulk deletes errors for the given sites via eloquent', function () {
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

it('no-ops when deleteBySites receives an empty site list via eloquent', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    Redirect::errors()->deleteBySites([]);

    expect(Redirect::errors()->all())->toHaveCount(1);
});

it('bulk deletes errors by id via eloquent', function () {
    $a = tap(Redirect::errors()->make()->url('/a')->site('default')->count(1))->save();
    $b = tap(Redirect::errors()->make()->url('/b')->site('default')->count(1))->save();
    $c = tap(Redirect::errors()->make()->url('/c')->site('default')->count(1))->save();

    Redirect::errors()->deleteByIds([$a->id(), $c->id()]);

    $urls = Redirect::errors()->all()->map->url();

    expect($urls)->not->toContain('/a')->toContain('/b')->not->toContain('/c');
});

it('no-ops when deleteByIds receives an empty id list via eloquent', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    Redirect::errors()->deleteByIds([]);

    expect(Redirect::errors()->all())->toHaveCount(1);
});

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
