<?php

use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('deletes the hit record when its redirect is deleted', function () {
    $redirect = Redirects::make()->id('r1')->source('/old')->destination('/new')->site('default');
    $redirect->save();

    Redirects::hits()->make()->redirect('r1')->count(3)->save();

    $redirect->delete();

    expect(Redirects::hits()->find('r1'))->toBeNull();
});

it('does nothing when the deleted redirect has no hit record', function () {
    $redirect = Redirects::make()->id('r2')->source('/x')->destination('/y')->site('default');
    $redirect->save();

    $redirect->delete();

    expect(Redirects::hits()->find('r2'))->toBeNull();
});
