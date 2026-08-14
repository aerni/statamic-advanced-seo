<?php

use Aerni\AdvancedSeo\Features\RedirectImportExport;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class);

beforeEach(function () {
    $this->installSimpleExcelPackage();

    config(['advanced-seo.redirects.enabled' => true]);
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    expect(RedirectImportExport::enabled())->toBeFalse();
});

it('is enabled on the pro edition', function () {
    expect(RedirectImportExport::enabled())->toBeTrue();
});

it('is disabled when the redirects feature is off', function () {
    config(['advanced-seo.redirects.enabled' => false]);

    expect(RedirectImportExport::enabled())->toBeFalse();
});

it('is disabled when simple-excel is not installed', function () {
    $this->uninstallPackages();

    expect(RedirectImportExport::enabled())->toBeFalse();
});
