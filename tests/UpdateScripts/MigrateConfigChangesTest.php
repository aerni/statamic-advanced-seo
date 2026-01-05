<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\MigrateConfigChanges;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

/**
 * Run the update script and clear Blink cache to force fresh data retrieval from the registry
 */
function runConfigMigrationScript(): void
{
    (new MigrateConfigChanges('aerni/advanced-seo'))->update();
    Blink::flush();
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

it('migrates sitemap config from indexing set to individual collections/taxonomies sets', function () {
    $indexingSet = Seo::find('site::indexing');

    $indexingSet->in('english')
        ->set('excluded_collections', ['blog', 'products'])
        ->set('excluded_taxonomies', ['tags', 'categories'])
        ->save();

    $indexingSet->in('german')
        ->set('origin', 'english')
        ->save();

    $indexingSet->in('french')
        ->set('origin', 'english')
        ->set('excluded_collections', ['products'])
        ->set('excluded_taxonomies', [])
        ->save();

    runConfigMigrationScript();

    // Assert collections: blog excluded in all its sites (english, german)
    $blogSet = Seo::find('collections::blog');
    expect($blogSet->config()->get('sitemap'))->toBeFalse(); // Excluded in all its sites
    expect($blogSet->in('english')->get('seo_sitemap_enabled'))->toBeNull();
    expect($blogSet->in('german')->get('seo_sitemap_enabled'))->toBeNull();

    // Assert collections: products excluded in all its sites (english, german, french)
    $productsSet = Seo::find('collections::products');
    expect($productsSet->config()->get('sitemap'))->toBeFalse(); // Excluded in all its sites
    expect($productsSet->in('english')->get('seo_sitemap_enabled'))->toBeNull();
    expect($productsSet->in('german')->get('seo_sitemap_enabled'))->toBeNull();
    expect($productsSet->in('french')->get('seo_sitemap_enabled'))->toBeNull();

    // Assert collections: pages has no sitemap config changes (not excluded anywhere)
    $pagesSet = Seo::find('collections::pages');
    expect($pagesSet->config()->get('sitemap'))->toBeNull();
    expect($pagesSet->in('english')->get('seo_sitemap_enabled'))->toBeNull();

    // Assert taxonomies: tags excluded in all its sites (english only)
    $tagsSet = Seo::find('taxonomies::tags');
    expect($tagsSet->config()->get('sitemap'))->toBeFalse(); // Excluded in all its sites
    expect($tagsSet->in('english')->get('seo_sitemap_enabled'))->toBeNull();

    // Assert taxonomies: categories excluded in all its sites (english, german)
    $categoriesSet = Seo::find('taxonomies::categories');
    expect($categoriesSet->config()->get('sitemap'))->toBeFalse(); // Excluded in all its sites
    expect($productsSet->in('english')->get('seo_sitemap_enabled'))->toBeNull();
    expect($productsSet->in('german')->get('seo_sitemap_enabled'))->toBeNull();

    // Assert old fields and origins are removed from indexing set
    $indexingSet = Seo::find('site::indexing');
    expect($indexingSet->in('english')->get('excluded_collections'))->toBeNull();
    expect($indexingSet->in('english')->get('excluded_taxonomies'))->toBeNull();
    expect($indexingSet->in('german')->get('excluded_collections'))->toBeNull();
    expect($indexingSet->in('german')->get('excluded_taxonomies'))->toBeNull();
    expect($indexingSet->in('german')->get('origin'))->toBeNull();
    expect($indexingSet->in('french')->get('excluded_collections'))->toBeNull();
    expect($indexingSet->in('french')->get('excluded_taxonomies'))->toBeNull();
    expect($indexingSet->in('french')->get('origin'))->toBeNull();
});
