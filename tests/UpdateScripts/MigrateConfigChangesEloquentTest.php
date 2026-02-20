<?php

use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateConfigChanges;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(UseEloquentDriver::class, PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Nav::shouldReceive('clearCachedUrls')->andReturn(null);

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
});

function runEloquentConfigMigration(): void
{
    $mock = Mockery::mock(MigrateConfigChanges::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $mock->shouldReceive('usesEloquentDriver')->andReturn(true);
    $mock->shouldReceive('migrateEloquentTables')->once();

    $mock->run();
}

it('consolidates old Eloquent site configs into defaults with origins', function () {
    DB::table('seo_set_configs')->insert([
        ['type' => 'site', 'handle' => 'general', 'data' => json_encode(['origins' => ['german' => 'english']]), 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'site', 'handle' => 'indexing', 'data' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'site', 'handle' => 'social_media', 'data' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
    ]);

    runEloquentConfigMigration();

    // Old configs should be deleted
    expect(DB::table('seo_set_configs')
        ->where('type', 'site')
        ->whereIn('handle', ['general', 'indexing', 'social_media'])
        ->count()
    )->toBe(0);

    // Defaults config should exist with merged origins
    $defaults = DB::table('seo_set_configs')
        ->where('type', 'site')
        ->where('handle', 'defaults')
        ->first();

    expect($defaults)->not->toBeNull();
    expect(json_decode($defaults->data, true))->toBe(['origins' => ['english' => null, 'german' => 'english']]);
});

it('consolidates old Eloquent site localizations into defaults per locale', function () {
    DB::table('seo_set_localizations')->insert([
        ['type' => 'site', 'handle' => 'general', 'locale' => 'english', 'data' => json_encode(['site_name' => 'My Site']), 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'site', 'handle' => 'indexing', 'locale' => 'english', 'data' => json_encode(['noindex' => true]), 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'site', 'handle' => 'general', 'locale' => 'german', 'data' => json_encode(['site_name' => 'Meine Seite']), 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'site', 'handle' => 'social_media', 'locale' => 'german', 'data' => json_encode(['og_image' => 'image.jpg']), 'created_at' => now(), 'updated_at' => now()],
    ]);

    runEloquentConfigMigration();

    // Old localizations should be deleted
    expect(DB::table('seo_set_localizations')
        ->where('type', 'site')
        ->whereIn('handle', ['general', 'indexing', 'social_media'])
        ->count()
    )->toBe(0);

    // English defaults: merged general + indexing data
    $english = DB::table('seo_set_localizations')
        ->where('type', 'site')
        ->where('handle', 'defaults')
        ->where('locale', 'english')
        ->first();

    expect($english)->not->toBeNull();
    expect(json_decode($english->data, true))->toBe([
        'site_name' => 'My Site',
        'noindex' => true,
    ]);

    // German defaults: merged general + social_media data
    $german = DB::table('seo_set_localizations')
        ->where('type', 'site')
        ->where('handle', 'defaults')
        ->where('locale', 'german')
        ->first();

    expect($german)->not->toBeNull();
    expect(json_decode($german->data, true))->toBe([
        'site_name' => 'Meine Seite',
        'og_image' => 'image.jpg',
    ]);
});

it('creates defaults config without origins when no old config has origins', function () {
    DB::table('seo_set_configs')->insert([
        ['type' => 'site', 'handle' => 'general', 'data' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'site', 'handle' => 'indexing', 'data' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
    ]);

    runEloquentConfigMigration();

    $defaults = DB::table('seo_set_configs')
        ->where('type', 'site')
        ->where('handle', 'defaults')
        ->first();

    expect($defaults)->not->toBeNull();
    expect(json_decode($defaults->data, true))->toBe([]);
});

it('creates clean defaults when no old site rows exist', function () {
    runEloquentConfigMigration();

    // Defaults config exists (created by saveSetsAndLocalizations) but has no consolidated origins
    $defaults = DB::table('seo_set_configs')
        ->where('type', 'site')
        ->where('handle', 'defaults')
        ->first();

    expect($defaults)->not->toBeNull();
    expect(json_decode($defaults->data, true))->toBe([]);
});
