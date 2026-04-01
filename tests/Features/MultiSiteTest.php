<?php

use Aerni\AdvancedSeo\Features\MultiSite;
use Statamic\Facades\Site;

it('is disabled on the free edition', function () {
    useFreeEdition();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    expect(MultiSite::enabled())->toBeFalse();
});

it('is disabled with a single site', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    expect(MultiSite::enabled())->toBeFalse();
});

it('is enabled on pro with multiple sites', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    expect(MultiSite::enabled())->toBeTrue();
});
