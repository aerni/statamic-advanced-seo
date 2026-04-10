<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateConfigChanges;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

function runConfigMigrationScript(): void
{
    (new MigrateConfigChanges)->run();
}

beforeEach(function () {
    // Mock Nav facade to avoid clearCachedUrls() errors
    Nav::shouldReceive('clearCachedUrls')->andReturn(null);

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
        'french' => ['name' => 'French', 'url' => '/fr', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    Collection::make('blog')->sites(['english', 'german'])->saveQuietly();
    Collection::make('products')->sites(['english', 'german', 'french'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english', 'german'])->saveQuietly();
});

it('enables the pro edition for existing v2 users', function () {
    $configPath = config_path('statamic/editions.php');

    File::ensureDirectoryExists(dirname($configPath));
    File::put($configPath, <<<'PHP'
<?php

return [

    'pro' => env('STATAMIC_PRO_ENABLED', false),

    'addons' => [
        //
    ],

];
PHP);

    runConfigMigrationScript();

    $contents = File::get($configPath);

    expect($contents)->toContain("'aerni/advanced-seo' => 'pro'");

    File::deleteDirectory(config_path('statamic'));
});

it('does not duplicate pro edition if already set', function () {
    $configPath = config_path('statamic/editions.php');

    File::ensureDirectoryExists(dirname($configPath));
    File::put($configPath, <<<'PHP'
<?php

return [

    'pro' => env('STATAMIC_PRO_ENABLED', false),

    'addons' => [
        'aerni/advanced-seo' => 'free',
    ],

];
PHP);

    runConfigMigrationScript();

    $contents = File::get($configPath);

    expect($contents)->toContain("'aerni/advanced-seo' => 'free'")
        ->and(substr_count($contents, 'aerni/advanced-seo'))->toBe(1);

    File::deleteDirectory(config_path('statamic'));
});

it('deletes title from config set', function () {
    Seo::find('collections::pages')->config()->set('title', 'Pages')->save();

    runConfigMigrationScript();

    expect(Seo::find('collections::pages')->config()->get('title'))->toBeNull();
});

it('migrates disabled collections/taxonomies from config to set configs', function () {
    config(['advanced-seo.disabled.collections' => ['blog', 'products', 'non-existing-collection']]);
    config(['advanced-seo.disabled.taxonomies' => ['tags', 'non-existing-collection']]);

    runConfigMigrationScript();

    expect(Seo::find('collections::pages')->config()->enabled())->toBeTrue();
    expect(Seo::find('collections::blog')->config()->enabled())->toBeFalse();
    expect(Seo::find('collections::products')->config()->enabled())->toBeFalse();
    expect(Seo::find('taxonomies::tags')->config()->enabled())->toBeFalse();
    expect(Seo::find('taxonomies::categories')->config()->enabled())->toBeTrue();
});

it('migrates origins from localizations to set configs', function () {
    $set = Seo::find('collections::products');
    $set->in('english')->save();
    $set->in('german')->set('origin', 'english')->save();
    $set->in('french')->set('origin', 'german')->save();

    runConfigMigrationScript();

    $set = Seo::find('collections::products');

    $origins = $set->config()->origins();
    expect($origins->get('english'))->toBeNull();
    expect($origins->get('german'))->toBe('english');
    expect($origins->get('french'))->toBe('german');

    expect($set->in('english')->get('origin'))->toBeNull();
    expect($set->in('german')->get('origin'))->toBeNull();
    expect($set->in('french')->get('origin'))->toBeNull();
});

it('migrates sitemap config from site defaults to individual collections/taxonomies sets', function () {
    config(['advanced-seo.sitemap.enabled' => true]);
    $siteDefaults = Seo::find('site::defaults');

    $siteDefaults->in('english')
        ->set('excluded_collections', ['blog', 'products'])
        ->set('excluded_taxonomies', ['tags', 'categories'])
        ->save();

    $siteDefaults->in('german')
        ->set('origin', 'english')
        ->save();

    $siteDefaults->in('french')
        ->set('origin', 'english')
        ->set('excluded_collections', ['products'])
        ->set('excluded_taxonomies', [])
        ->save();

    // Seed a stale seo_sitemap_enabled value on a localization to verify cleanup
    Seo::find('collections::pages')->in('english')
        ->set('seo_sitemap_enabled', true)
        ->save();

    runConfigMigrationScript();

    // Assert collections: blog excluded in any site → disabled
    $blogSet = Seo::find('collections::blog');
    expect($blogSet->config()->get('sitemap'))->toBeFalse();

    // Assert collections: products excluded in any site → disabled
    $productsSet = Seo::find('collections::products');
    expect($productsSet->config()->get('sitemap'))->toBeFalse();

    // Assert collections: pages not excluded anywhere → enabled
    $pagesSet = Seo::find('collections::pages');
    expect($pagesSet->config()->get('sitemap'))->toBeTrue();

    // Assert taxonomies: tags excluded in any site → disabled
    $tagsSet = Seo::find('taxonomies::tags');
    expect($tagsSet->config()->get('sitemap'))->toBeFalse();

    // Assert taxonomies: categories excluded in any site → disabled
    $categoriesSet = Seo::find('taxonomies::categories');
    expect($categoriesSet->config()->get('sitemap'))->toBeFalse();

    // Assert seo_sitemap_enabled is cleaned up from all localizations
    Seo::all()
        ->filter(fn ($set) => in_array($set->type(), ['collections', 'taxonomies']))
        ->each(fn ($set) => $set->localizations()->each(
            fn ($localization) => expect($localization->get('seo_sitemap_enabled'))->toBeNull()
        ));

    // Assert old fields are removed from site defaults
    $siteDefaults = Seo::find('site::defaults');
    expect($siteDefaults->in('english')->get('excluded_collections'))->toBeNull();
    expect($siteDefaults->in('english')->get('excluded_taxonomies'))->toBeNull();
    expect($siteDefaults->in('german')->get('excluded_collections'))->toBeNull();
    expect($siteDefaults->in('german')->get('excluded_taxonomies'))->toBeNull();
    expect($siteDefaults->in('french')->get('excluded_collections'))->toBeNull();
    expect($siteDefaults->in('french')->get('excluded_taxonomies'))->toBeNull();
});

it('skips sitemap migration and cleans up fields when sitemap is disabled', function () {
    config(['advanced-seo.sitemap.enabled' => false]);

    Seo::find('site::defaults')->in('english')
        ->set('excluded_collections', ['blog', 'products'])
        ->set('excluded_taxonomies', ['tags'])
        ->save();

    // Seed a stale seo_sitemap_enabled value on a localization to verify cleanup
    Seo::find('collections::blog')->in('english')
        ->set('seo_sitemap_enabled', true)
        ->save();

    runConfigMigrationScript();

    // Assert: Collections and taxonomies should not have sitemap config changes
    expect(Seo::find('collections::blog')->config()->get('sitemap'))->toBeNull();
    expect(Seo::find('collections::products')->config()->get('sitemap'))->toBeNull();
    expect(Seo::find('taxonomies::tags')->config()->get('sitemap'))->toBeNull();

    // Assert: seo_sitemap_enabled is still cleaned up from localizations even when sitemap is disabled
    Seo::all()
        ->filter(fn ($set) => in_array($set->type(), ['collections', 'taxonomies']))
        ->each(fn ($set) => $set->localizations()->each(
            fn ($localization) => expect($localization->get('seo_sitemap_enabled'))->toBeNull()
        ));

    // Assert: Old fields are still removed from site defaults
    $siteDefaults = Seo::find('site::defaults');
    expect($siteDefaults->in('english')->get('excluded_collections'))->toBeNull();
    expect($siteDefaults->in('english')->get('excluded_taxonomies'))->toBeNull();
});

it('migrates social images generator config based on localization coverage', function () {
    config()->set('advanced-seo.social_images.generator.enabled', true);

    $siteDefaults = Seo::find('site::defaults');

    $siteDefaults->in('english')
        ->set('social_images_generator_collections', ['products'])
        ->save();

    $siteDefaults->in('german')
        ->set('origin', 'english')
        ->set('social_images_generator_collections', ['blog'])
        ->save();

    $siteDefaults->in('french')
        ->set('origin', 'english')
        ->set('social_images_generator_collections', ['products'])
        ->save();

    runConfigMigrationScript();

    $productsSet = Seo::find('collections::products');
    expect($productsSet->config()->get('social_images_generator'))->toBeTrue();
    expect($productsSet->in('english')->get('seo_generate_social_images'))->toBeFalse();
    expect($productsSet->in('german')->get('seo_generate_social_images'))->toBeFalse();
    expect($productsSet->in('french')->get('seo_generate_social_images'))->toBeFalse();

    $blogSet = Seo::find('collections::blog');
    expect($blogSet->config()->get('social_images_generator'))->toBeTrue();
    expect($blogSet->in('english')->get('seo_generate_social_images'))->toBeFalse();
    expect($blogSet->in('german')->get('seo_generate_social_images'))->toBeFalse();

    $pagesSet = Seo::find('collections::pages');
    expect($pagesSet->config()->get('social_images_generator'))->toBeFalse();
    expect($pagesSet->in('english')->get('seo_generate_social_images'))->toBeNull();
});

it('migrates social images generator: preserves existing true values', function () {
    config()->set('advanced-seo.social_images.generator.enabled', true);

    Seo::find('site::defaults')->in('english')
        ->set('social_images_generator_collections', ['products'])
        ->save();

    Seo::find('collections::products')
        ->in('german')
        ->set('seo_generate_social_images', true)
        ->save();

    runConfigMigrationScript();

    $productsSet = Seo::find('collections::products');
    expect($productsSet->config()->get('social_images_generator'))->toBeTrue();
    expect($productsSet->in('english')->get('seo_generate_social_images'))->toBeFalse();
    expect($productsSet->in('german')->get('seo_generate_social_images'))->toBeTrue();
    expect($productsSet->in('french')->get('seo_generate_social_images'))->toBeFalse();
});

it('migrates social images generator: cleans up old field from site defaults', function () {
    Seo::find('site::defaults')->in('english')
        ->set('social_images_generator_collections', ['products'])
        ->save();

    Seo::find('site::defaults')->in('german')
        ->set('social_images_generator_collections', ['products'])
        ->save();

    runConfigMigrationScript();

    $siteDefaults = Seo::find('site::defaults');
    expect($siteDefaults->in('english')->get('social_images_generator_collections'))->toBeNull();
    expect($siteDefaults->in('german')->get('social_images_generator_collections'))->toBeNull();
});

it('skips social images generator migration and cleans up fields when generator is disabled', function () {
    config(['advanced-seo.social_images.generator.enabled' => false]);

    Seo::find('site::defaults')->in('english')
        ->set('social_images_generator_collections', ['products', 'blog'])
        ->save();

    runConfigMigrationScript();

    // Assert: Collections should not have social_images_generator config changes
    expect(Seo::find('collections::products')->config()->get('social_images_generator'))->toBeNull();
    expect(Seo::find('collections::blog')->config()->get('social_images_generator'))->toBeNull();
    expect(Seo::find('collections::pages')->config()->get('social_images_generator'))->toBeNull();

    // Assert: Old field is still removed from site defaults
    expect(Seo::find('site::defaults')->in('english')->get('social_images_generator_collections'))->toBeNull();
});

it('calls migrateEloquentTables and skips file-based migrations for Eloquent users', function () {
    // Set title and origin BEFORE running the migration
    Seo::find('collections::pages')->config()->set('title', 'Pages')->save();
    Seo::find('collections::products')->in('german')->set('origin', 'english')->save();

    $mock = Mockery::mock(MigrateConfigChanges::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $mock->shouldReceive('usesEloquentDriver')->andReturn(true);
    $mock->shouldReceive('migrateEloquentTables')->once();
    $mock->shouldReceive('consolidateEloquentSiteSeoSets')->once();

    $mock->run();

    // Title should NOT be removed (handled by database migration)
    expect(Seo::find('collections::pages')->config()->get('title'))->toBe('Pages');

    // Origins should NOT be migrated (handled by database migration)
    expect(Seo::find('collections::products')->in('german')->get('origin'))->toBe('english');
});

it('migrates single-site inlined data from config to localization', function () {
    // Simulate old single-site format: data stored in config under 'data' key
    Seo::find('collections::pages')
        ->config()
        ->set('data', [
            'seo_title' => 'Default Page Title',
            'seo_description' => 'A page description',
        ])
        ->save();

    runConfigMigrationScript();

    // Assert: Data was moved from config to localization
    $pagesSet = Seo::find('collections::pages');
    expect($pagesSet->config()->get('data'))->toBeNull();
    expect($pagesSet->inDefaultSite()->get('seo_title'))->toBe('Default Page Title');
    expect($pagesSet->inDefaultSite()->get('seo_description'))->toBe('A page description');
});
