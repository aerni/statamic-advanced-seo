<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateConfigChanges;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\YAML;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

function runFileConfigMigration(): void
{
    (new MigrateConfigChanges)->run();
}

beforeEach(function () {
    Nav::shouldReceive('clearCachedUrls')->andReturn(null);

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
});

it('deletes old site config files during consolidation', function () {
    $siteDir = Stache::store('seo-set-configs')->directory().'site';

    File::ensureDirectoryExists($siteDir);

    File::put("{$siteDir}/general.yaml", YAML::dump(['origins' => ['german' => 'english']]));
    File::put("{$siteDir}/indexing.yaml", YAML::dump([]));
    File::put("{$siteDir}/social_media.yaml", YAML::dump([]));

    runFileConfigMigration();

    expect(File::exists("{$siteDir}/general.yaml"))->toBeFalse();
    expect(File::exists("{$siteDir}/indexing.yaml"))->toBeFalse();
    expect(File::exists("{$siteDir}/social_media.yaml"))->toBeFalse();
});

it('consolidates old site localizations into defaults per locale', function () {
    $siteDir = Stache::store('seo-set-configs')->directory().'site';

    collect(['general', 'indexing', 'social_media', 'analytics', 'favicons'])
        ->each(fn ($handle) => File::delete("{$siteDir}/{$handle}.yaml"));

    File::ensureDirectoryExists("{$siteDir}/english");

    File::put("{$siteDir}/english/general.yaml", YAML::dump([
        'site_name' => 'Legacy Site Name',
    ]));
    File::put("{$siteDir}/english/indexing.yaml", YAML::dump([
        'noindex' => true,
    ]));

    runFileConfigMigration();

    $siteDefaults = Seo::find('site::defaults')->in('english');

    expect($siteDefaults->get('site_name'))->toBe('Legacy Site Name');
    expect($siteDefaults->get('noindex'))->toBeTrue();
    expect(File::exists("{$siteDir}/english/general.yaml"))->toBeFalse();
    expect(File::exists("{$siteDir}/english/indexing.yaml"))->toBeFalse();
    expect(File::exists("{$siteDir}/english/defaults.yaml"))->toBeTrue();
});

it('consolidates v2 single-site inline data from root YAMLs into the default locale defaults.yaml', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    $siteDir = Stache::store('seo-set-configs')->directory().'site';

    File::ensureDirectoryExists($siteDir);

    File::put("{$siteDir}/general.yaml", YAML::dump([
        'title' => 'General',
        'data' => [
            'site_name' => 'Legacy Site Name',
            'separator' => '|',
        ],
    ]));
    File::put("{$siteDir}/indexing.yaml", YAML::dump([
        'title' => 'Indexing',
        'data' => [
            'noindex' => true,
        ],
    ]));

    runFileConfigMigration();

    $siteDefaults = Seo::find('site::defaults')->in('english');

    expect($siteDefaults->get('site_name'))->toBe('Legacy Site Name');
    expect($siteDefaults->get('separator'))->toBe('|');
    expect($siteDefaults->get('noindex'))->toBeTrue();
    expect(File::exists("{$siteDir}/general.yaml"))->toBeFalse();
    expect(File::exists("{$siteDir}/indexing.yaml"))->toBeFalse();
    expect(File::exists("{$siteDir}/english/defaults.yaml"))->toBeTrue();
});
