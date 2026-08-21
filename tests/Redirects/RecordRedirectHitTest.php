<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Jobs\RecordRedirectHitJob;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('records a hit for the redirect', function () {
    (new RecordRedirectHitJob('abc'))->handle();

    expect(Redirect::hits()->find('abc')->count())->toBe(1);
});
