<?php

use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Jobs\RecordRedirectHitJob;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('records a hit for the redirect', function () {
    (new RecordRedirectHitJob('abc'))->handle();

    expect(Redirects::hits()->find('abc')->count())->toBe(1);
});
