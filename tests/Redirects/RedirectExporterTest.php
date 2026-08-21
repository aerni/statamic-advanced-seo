<?php

use Aerni\AdvancedSeo\Enums\RedirectExportFormat;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    $this->installSimpleExcelPackage();
    $this->actingAs(tap(User::make()->makeSuper())->save());
});

function parseCsv(string $content): array
{
    $lines = array_filter(explode("\n", trim($content)));
    $headers = str_getcsv(array_shift($lines));

    return array_map(fn ($line) => array_combine($headers, str_getcsv($line)), $lines);
}

it('exports redirects as csv', function () {
    Redirect::make()->id('one')->source('/old')->destination('/new')->responseCode(RedirectResponseCode::Permanent)->enabled(true)->preserveQueryString(true)->site('default')->save();
    Redirect::make()->id('two')->source('/gone')->responseCode(RedirectResponseCode::Gone)->enabled(false)->site('default')->save();

    $rows = parseCsv(Redirect::export(RedirectExportFormat::Csv));

    expect($rows)->toHaveCount(2);
    expect($rows[0]['source'])->toBe('/old');
    expect($rows[0]['destination'])->toBe('/new');
    expect($rows[0]['response_code'])->toBe('301');
    expect($rows[0]['preserve_query_string'])->toBe('true');
    expect($rows[0]['enabled'])->toBe('true');
    expect($rows[0]['site'])->toBe('default');
    expect($rows[1]['source'])->toBe('/gone');
    expect($rows[1]['destination'])->toBe('');
    expect($rows[1]['response_code'])->toBe('410');
    expect($rows[1]['enabled'])->toBe('false');
});

it('exports redirects as json with native types', function () {
    Redirect::make()->id('one')->source('/old')->destination('/new')->responseCode(RedirectResponseCode::Permanent)->enabled(true)->preserveQueryString(false)->site('default')->save();
    Redirect::make()->id('two')->source('/gone')->responseCode(RedirectResponseCode::Gone)->enabled(true)->site('default')->save();

    $rows = json_decode(Redirect::export(RedirectExportFormat::Json), true);

    expect($rows)->toHaveCount(2);
    expect($rows[0]['source'])->toBe('/old');
    expect($rows[0]['response_code'])->toBe(301);
    expect($rows[0]['enabled'])->toBeTrue();
    expect($rows[0]['preserve_query_string'])->toBeFalse();
    expect($rows[0]['site'])->toBe('default');
    expect($rows[1]['destination'])->toBeNull();
    expect($rows[1]['response_code'])->toBe(410);
});

it('always includes the site column', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $rows = parseCsv(Redirect::export(RedirectExportFormat::Csv));

    expect($rows[0]['site'])->toBe('default');
});
