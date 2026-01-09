<?php

use Statamic\Facades\Site;
use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Statamic\Contracts\Entries\Collection as StatamicCollection;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
        'french' => ['name' => 'French', 'url' => '/fr', 'locale' => 'fr'],
    ]);

    CollectionFacade::make('articles')
        ->title('My Articles')
        ->icon('foo')
        ->sites(['english', 'german'])
        ->saveQuietly();

    CollectionFacade::make('blog')->saveQuietly();

    TaxonomyFacade::make('tags')->saveQuietly();
});

it('can get the id', function () {
    $set = Seo::find('collections::articles');

    expect($set->id())->toBe('collections::articles');
});

it('can get the type', function () {
    $set = Seo::find('collections::articles');

    expect($set->type())->toBe('collections');
});

it('can get the handle', function () {
    $set = Seo::find('collections::articles');

    expect($set->handle())->toBe('articles');
});

it('can get the title', function () {
    $set = Seo::find('collections::articles');

    expect($set->title())->toBe('My Articles');
});

it('can get the icon', function () {
    $set = Seo::find('collections::articles');

    expect($set->icon())->toBe('foo');
});

it('can get the blueprint', function () {
    $collectionSet = Seo::find('collections::articles');

    expect($collectionSet->blueprint('localization'))
        ->toBe(\Aerni\AdvancedSeo\Blueprints\ContentSeoSetLocalizationBlueprint::class);
    expect($collectionSet->blueprint('config'))
        ->toBe(\Aerni\AdvancedSeo\Blueprints\ContentSeoSetConfigBlueprint::class);

    $taxonomySet = Seo::find('taxonomies::tags');

    expect($taxonomySet->blueprint('localization'))
        ->toBe(\Aerni\AdvancedSeo\Blueprints\ContentSeoSetLocalizationBlueprint::class);
    expect($taxonomySet->blueprint('config'))
        ->toBe(\Aerni\AdvancedSeo\Blueprints\ContentSeoSetConfigBlueprint::class);

    $siteSet = Seo::find('site::general');

    expect($siteSet->blueprint('localization'))
        ->toBe('Aerni\\AdvancedSeo\\Blueprints\\GeneralBlueprint');
    expect($siteSet->blueprint('config'))
        ->toBe(\Aerni\AdvancedSeo\Blueprints\SiteSeoSetConfigBlueprint::class);
});

it('throws exception for invalid blueprint type', function () {
    $set = Seo::find('collections::articles');

    $set->blueprint('invalid');
})->throws(\Exception::class, "No blueprint defined for SEO set type 'collections' with blueprint type 'invalid'");

it('can get the parent', function () {
    $collectionSet = Seo::find('collections::articles');

    expect($collectionSet->parent())->toBeInstanceOf(StatamicCollection::class);

    $taxonomySet = Seo::find('taxonomies::tags');

    expect($taxonomySet->parent())->toBeInstanceOf(Taxonomy::class);

    $siteSet = Seo::find('site::general');

    expect($siteSet->parent())->toBeNull();
});

it('can get the enabled state', function () {
    $set = Seo::find('collections::articles');

    expect($set->enabled())->toBeTrue();
});

it('can get the config', function () {
    $set = Seo::find('collections::articles');
    $config = $set->config();

    expect($config)->toBeInstanceOf(SeoSetConfig::class);
    expect($config->seoSet())->toBe($set);
});

it('can get the origins', function () {
    $set = Seo::find('collections::articles');
    $origins = $set->origins();

    expect($origins)->toBeInstanceOf(Collection::class);
    expect($origins->keys()->all())->toBe(['english', 'german']);
});

it('can get the sites', function () {
    $collectionSet = Seo::find('collections::articles');

    expect($collectionSet->sites()->keys()->all())->toBe(['english', 'german']);

    $siteSet = Seo::find('site::general');

    expect($siteSet->sites()->keys()->all())->toBe(Site::all()->keys()->all());
});

it('can get the localizations', function () {
    $set = Seo::find('collections::articles');
    $localizations = $set->localizations();

    expect($localizations)->toBeInstanceOf(Collection::class);
    expect($localizations)->toHaveCount(2);
    expect($localizations->first())->toBeInstanceOf(SeoSetLocalization::class);
    expect($localizations->first()->seoSet())->toBe($set);
});

it('can get the selected site', function () {
    $set = Seo::find('collections::articles');

    Site::setSelected('german');

    expect($set->selectedSite())->toBe(Site::selected()->handle());

    Site::setSelected('french');

    /* Falls back to the default site if it doesn't exist in the selected site. */
    expect($set->selectedSite())->toBe(invade($set)->defaultSite());
});

it('can get a specific localization', function () {
    $set = Seo::find('collections::articles');

    expect($set->in('english'))->toBeInstanceOf(SeoSetLocalization::class);
    expect($set->in('nonexistent'))->toBeNull();
});

it('can get the localization of the selected site', function () {
    $set = Seo::find('collections::articles');

    Site::setSelected('german');
    expect($set->inSelectedSite()->locale())->toBe(Site::selected()->handle());

    Site::setSelected('french');
    expect($set->inSelectedSite('french'))->toBeNull();
});

it('can get the localization of the default site', function () {
    $set = Seo::find('collections::articles');

    expect($set->inDefaultSite()->locale())->toBe(invade($set)->defaultSite());
});

it('can save the set', function () {
    $set = new SeoSet(
        type: 'collections',
        handle: 'articles',
        title: 'Articles',
        icon: 'icon'
    );

    /* Create a mock config that expects save() to be called */
    $mockConfig = Mockery::mock(SeoSetConfig::class);
    $mockConfig->shouldReceive('save')->once()->andReturnSelf();
    $mockConfig->shouldReceive('seoSet')->andReturn($mockConfig);

    /* Mock the SeoConfig facade to return our mock */
    SeoConfig::shouldReceive('findOrMake')
        ->with('collections::articles')
        ->once()
        ->andReturn($mockConfig);

    /* Call save() and verify it returns the set */
    expect($set->save())->toBe($set);
});

it('can delete the set', function () {
    $set = new SeoSet(
        type: 'collections',
        handle: 'articles',
        title: 'Articles',
        icon: 'icon'
    );

    /* Create a mock config that expects delete() to be called */
    $mockConfig = Mockery::mock(SeoSetConfig::class);
    $mockConfig->shouldReceive('delete')->once()->andReturn(true);
    $mockConfig->shouldReceive('seoSet')->andReturn($mockConfig);

    /* Mock the SeoConfig facade to return our mock */
    SeoConfig::shouldReceive('findOrMake')
        ->with('collections::articles')
        ->once()
        ->andReturn($mockConfig);

    /* Call delete() and verify it returns true */
    expect($set->delete())->toBeTrue();
});

it('returns the ID as queryable value', function () {
    $set = Seo::find('collections::articles');

    expect($set->toQueryableValue())->toBe($set->id());
});

/**
 * The toArray() method is not tested here because it requires:
 * - User authentication context (User::current())
 * - URL routing setup (editUrl() methods)
 * - Authorization policy checks
 *
 * This method should be tested at a higher level (API/Controller tests)
 * where the full application context is available.
 *
 * API Contract:
 * Returns an array with keys: id, type, handle, title, icon, enabled,
 * sitemap, social_images_generator, localization_url, config_url, configurable
 */

it('flushes the blink cache', function () {
    $set = new SeoSet(
        type: 'collections',
        handle: 'articles',
        title: 'Articles',
        icon: 'icon'
    );

    Blink::put("advanced-seo::{$set->id()}::", 'test');

    expect(Blink::has("advanced-seo::{$set->id()}::"))->toBeTrue();

    $set->flushBlink();

    expect(Blink::has("advanced-seo::{$set->id()}::"))->toBeFalse();
});

it('caches set-specific config, localizations, and parent', function () {
    $set = Seo::find('collections::articles');

    /* Trigger caching */
    $set->config();
    $set->localizations();
    $set->parent();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeTrue();
    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeTrue();
    expect(Blink::has('advanced-seo::collections::articles::parent'))->toBeTrue();
});

it('clears set-specific caches when config is saved', function () {
    $set = Seo::find('collections::articles');

    /* Trigger caching */
    $set->config();
    $set->localizations();
    $set->parent();

    /* Manually add feature cache (can't trigger naturally in test) */
    Blink::put('advanced-seo::collections::articles::features::sitemap::default', true);

    $set->config()->save();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::parent'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::features::sitemap::default'))->toBeFalse();
});

it('clears set-specific caches when localization is saved', function () {
    $set = Seo::find('collections::articles');

    /* Trigger caching */
    $set->config();
    $set->localizations();
    $set->parent();

    /* Manually add feature cache (can't trigger naturally in test) */
    Blink::put('advanced-seo::collections::articles::features::sitemap::default', true);

    $set->inDefaultSite()->save();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::parent'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::features::sitemap::default'))->toBeFalse();
});

it('only clears caches for the specific set being saved', function () {
    $articlesSet = Seo::find('collections::articles');
    $articlesSet->config();

    $blogSet = Seo::find('collections::blog');
    $blogSet->config();

    $articlesSet->config()->save();

    /* Articles caches should be cleared */
    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();

    /* Blog caches should remain */
    expect(Blink::has('advanced-seo::collections::blog::config'))->toBeTrue();
});
