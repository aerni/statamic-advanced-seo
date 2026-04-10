<?php

use Aerni\AdvancedSeo\Contracts\SeoSetConfig as Contract;
use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;
use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Fields\Blueprint;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
        'french' => ['name' => 'French', 'url' => '/fr', 'locale' => 'fr'],
    ]);

    CollectionFacade::make('articles')
        ->sites(['english', 'german'])
        ->saveQuietly();

    TaxonomyFacade::make('tags')->saveQuietly();
});

it('implements the contract interface', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config)->toBeInstanceOf(Contract::class);
});

it('can get the id', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->id())->toBe('collections::articles');
});

it('can get the type', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->type())->toBe('collections');
});

it('can get the handle', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->handle())->toBe('articles');
});

it('can get and set the enabled state', function () {
    $config = Seo::find('collections::articles')->config();

    /* Default state */
    expect($config->enabled())->toBeTrue();

    /* Can disable */
    $config->enabled(false);
    expect($config->enabled())->toBeFalse();

    /* Can enable */
    $config->enabled(true);
    expect($config->enabled())->toBeTrue();
});

it('can get and set the editable state', function () {
    $config = Seo::find('collections::articles')->config();

    /* Default state */
    expect($config->editable())->toBeTrue();

    /* Can disable */
    $config->editable(false);
    expect($config->editable())->toBeFalse();

    /* Can enable */
    $config->editable(true);
    expect($config->editable())->toBeTrue();
});

it('returns true for enabled on site type sets regardless of value', function () {
    $config = Seo::find('site::defaults')->config();

    /* Trying to disable a site config should still return true */
    $config->enabled(false);

    expect($config->enabled())->toBeTrue();
});

it('can get and set origins', function () {
    $config = Seo::find('collections::articles')->config();

    /* Default empty origins */
    expect($config->origins())->toBeInstanceOf(Collection::class);

    /* Can set origins */
    $config->origins(['german' => 'english']);

    expect($config->origins()->all())->toBe(['english' => null, 'german' => 'english']);
});

it('filters invalid sites from origins when setting', function () {
    $config = Seo::find('collections::articles')->config();

    /* French is not a valid site for this collection */
    $config->origins(['german' => 'english', 'french' => 'english']);

    expect($config->origins()->has('french'))->toBeFalse();
    expect($config->origins()->get('german'))->toBe('english');
});

it('filters invalid origin values when setting', function () {
    $config = Seo::find('collections::articles')->config();

    /* French is not a valid origin for articles collection */
    $config->origins(['german' => 'french']);

    expect($config->origins()->get('german'))->toBeNull();
});

it('keeps null values in origins', function () {
    $config = Seo::find('collections::articles')->config();

    $config->origins(['english' => null, 'german' => 'english']);

    expect($config->origins()->get('english'))->toBeNull();
    expect($config->origins()->get('german'))->toBe('english');
});

it('can get the blueprint', function () {
    $blueprint = Seo::find('collections::articles')->config()->blueprint();
    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('content_config');

    $blueprint = Seo::find('taxonomies::tags')->config()->blueprint();
    expect($blueprint->handle())->toBe('content_config');

    $blueprint = Seo::find('site::defaults')->config()->blueprint();
    expect($blueprint->handle())->toBe('site_config');
});

it('can get the sites', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->sites())->toBeInstanceOf(Collection::class);
    expect($config->sites()->keys()->all())->toBe(['english', 'german']);

    $siteConfig = Seo::find('site::defaults')->config();

    expect($siteConfig->sites()->keys()->all())->toBe(Site::all()->keys()->all());
});

it('can get and set data values', function () {
    $config = Seo::find('collections::articles')->config();

    $config->set('sitemap', true);
    $config->set('social_images_generator', false);

    expect($config->get('sitemap'))->toBeTrue();
    expect($config->get('social_images_generator'))->toBeFalse();
});

it('can merge data', function () {
    $config = Seo::find('collections::articles')->config();

    $config->merge([
        'sitemap' => true,
        'social_images_generator' => false,
    ]);

    expect($config->get('sitemap'))->toBeTrue();
    expect($config->get('social_images_generator'))->toBeFalse();
});

it('can get file data', function () {
    $config = Seo::find('collections::articles')->config();

    $config->enabled(true);
    $config->origins(['german' => 'english']);
    $config->set('sitemap', true);

    expect($config->fileData())->toBe([
        'enabled' => true,
        'editable' => true,
        'origins' => [
            'english' => null,
            'german' => 'english',
        ],
        'sitemap' => true,
    ]);
});

it('excludes enabled and editable from file data for site type', function () {
    $config = Seo::find('site::defaults')->config();

    expect($config->fileData())->not->toHaveKey('enabled');
    expect($config->fileData())->not->toHaveKey('editable');
});

it('excludes empty origins from file data', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->fileData())->not->toHaveKey('origins');
});

it('can get the path', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->path())->toContain('collections/articles.yaml');
});

it('can get the edit url', function () {
    $config = Seo::find('collections::articles')->config();

    expect($config->editUrl())->toContain('advanced-seo/collections/articles/config');
});

it('can save the config', function () {
    Event::fake([SeoSetConfigSaved::class]);

    $config = Seo::find('collections::articles')->config();

    $result = $config->save();

    expect($result)->toBeInstanceOf(SeoSetConfig::class);

    Event::assertDispatched(SeoSetConfigSaved::class, function ($event) {
        return $event->config->id() === 'collections::articles';
    });
});

it('persists data when saved', function () {
    $config = Seo::find('collections::articles')->config();

    $config->set('sitemap', true)->save();

    /* Clear the stache to ensure we're reading from disk */
    clearStache();

    $freshConfig = Seo::find('collections::articles')->config();

    expect($freshConfig->get('sitemap'))->toBeTrue();
});

it('can delete the config', function () {
    Event::fake([SeoSetConfigDeleted::class]);

    $config = Seo::find('collections::articles')->config();

    $config->save();

    $result = $config->delete();

    expect($result)->toBeTrue();

    Event::assertDispatched(SeoSetConfigDeleted::class, function ($event) {
        return $event->config->id() === 'collections::articles';
    });

    expect(SeoConfig::find('collections::articles'))->toBeNull();
});

it('removes localization field values of disabled features when saving the set config', function () {
    $seoSet = Seo::find('collections::articles');

    $seoSet->inDefaultSite()
        ->set('seo_title', 'foo')
        ->set('seo_sitemap_priority', '0.8')
        ->save();

    $seoSet->config()
        ->set('sitemap', false)
        ->save();

    /* Ensure we get a fresh localization from the stache */
    clearStache();

    $localization = $seoSet->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('foo');
    expect($localization->get('seo_sitemap_priority'))->toBeNull();
});

it('can get and set the seoSet', function () {
    $config = SeoConfig::make();
    $seoSet = Seo::find('collections::articles');

    $config->seoSet($seoSet);

    expect($config->seoSet())->toBe($seoSet);
    expect($config->seoSet()->id())->toBe('collections::articles');
});

it('can set seoSet by id string', function () {
    $config = SeoConfig::make();

    $config->seoSet('collections::articles');

    expect($config->seoSet())->toBeInstanceOf(SeoSet::class);
    expect($config->seoSet()->id())->toBe('collections::articles');
});

it('can get default values from the blueprint', function () {
    $config = Seo::find('collections::articles')->config();

    $defaultValues = $config->defaultValues();

    expect($defaultValues)->toBeInstanceOf(Collection::class);
});

it('flushes blink cache when saved', function () {
    $seoSet = Seo::find('collections::articles');
    $config = $seoSet->config();

    /* Trigger caching */
    $seoSet->config();
    $seoSet->localizations();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeTrue();

    $config->save();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();
});

it('flushes blink cache when deleted', function () {
    $seoSet = Seo::find('collections::articles');
    $config = $seoSet->config();
    $config->save();

    /* Trigger caching */
    $seoSet->config();
    $seoSet->localizations();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeTrue();

    $config->delete();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();
});

it('returns origins mapped to sites for multi-site collections', function () {
    $config = Seo::find('collections::articles')->config();

    /* Collection has english and german sites */
    $origins = $config->origins();

    expect($origins)->toHaveCount(2);
    expect($origins->keys()->all())->toBe(['english', 'german']);
});

it('returns origins as-is for single-site collections', function () {
    CollectionFacade::make('single')
        ->sites(['english'])
        ->saveQuietly();

    $config = Seo::find('collections::single')->config();

    expect($config->origins())->toBeEmpty();
});
