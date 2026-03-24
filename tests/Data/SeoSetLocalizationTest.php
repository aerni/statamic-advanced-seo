<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as Contract;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationDeleted;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationSaved;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\SeoSets\AugmentedSeoSetLocalization;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Sites\Site as SiteObject;
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

    CollectionFacade::make('blog')->saveQuietly();

    TaxonomyFacade::make('tags')->saveQuietly();
});

it('implements the contract interface', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization)->toBeInstanceOf(Contract::class);
});

it('implements the Augmentable interface', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization)->toBeInstanceOf(Augmentable::class);
});

it('can get and set the locale', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization->locale())->toBe('english');

    $localization->locale('german');

    expect($localization->locale())->toBe('german');
});

it('can get the id', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization->id())->toBe('collections::articles::english');
});

it('can get the type', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization->type())->toBe('collections');

    $taxLocalization = Seo::find('taxonomies::tags')->inDefaultSite();

    expect($taxLocalization->type())->toBe('taxonomies');

    $siteLocalization = Seo::find('site::defaults')->inDefaultSite();

    expect($siteLocalization->type())->toBe('site');
});

it('can get the handle', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization->handle())->toBe('articles');
});

it('can get the site', function () {
    $localization = Seo::find('collections::articles')->in('german');

    expect($localization->site())->toBeInstanceOf(SiteObject::class);
    expect($localization->site()->handle())->toBe('german');
});

it('can get the sites', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization->sites())->toBeInstanceOf(Collection::class);
    expect($localization->sites()->keys()->all())->toBe(['english', 'german']);
});

it('can get the blueprint for collections', function () {
    $blueprint = Seo::find('collections::articles')->inDefaultSite()->blueprint();

    expect($blueprint->handle())->toBe('content_localization');
});

it('can get the blueprint for taxonomies', function () {
    $blueprint = Seo::find('taxonomies::tags')->inDefaultSite()->blueprint();

    expect($blueprint->handle())->toBe('content_localization');
});

it('can get the blueprint for site defaults', function () {
    $blueprint = Seo::find('site::defaults')->inDefaultSite()->blueprint();

    expect($blueprint->handle())->toBe('site_localization');
});

it('can get blueprint fields', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    $fields = $localization->blueprintFields();

    expect($fields)->toBeArray();
    expect($fields)->not->toBeEmpty();
});

it('can get the path', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    expect($localization->path())->toContain('collections/english/articles.yaml');
});

it('can get the edit url', function () {
    $config = Seo::find('collections::articles')->inDefaultSite();

    expect($config->editUrl())->toContain('advanced-seo/collections/articles/english');
});

it('can get and set the seoSet', function () {
    $localization = SeoLocalization::make();
    $seoSet = Seo::find('collections::articles');

    $localization->seoSet($seoSet)->locale('english');

    expect($localization->seoSet())->toBe($seoSet);
});

it('can set seoSet by id string', function () {
    $localization = SeoLocalization::make()
        ->seoSet('collections::articles')
        ->locale('english');

    expect($localization->seoSet())->toBeInstanceOf(SeoSet::class);
    expect($localization->seoSet()->id())->toBe('collections::articles');
});

it('can get and set data values', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    $localization->set('seo_title', 'Test Title');
    $localization->set('seo_description', 'Test Description');

    expect($localization->get('seo_title'))->toBe('Test Title');
    expect($localization->get('seo_description'))->toBe('Test Description');
});

it('can save the localization', function () {
    Event::fake([SeoSetLocalizationSaved::class]);

    $localization = Seo::find('collections::articles')->inDefaultSite();
    $localization->set('seo_title', 'Test');

    $result = $localization->save();

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);

    Event::assertDispatched(SeoSetLocalizationSaved::class, function ($event) {
        return $event->localization->id() === 'collections::articles::english';
    });
});

it('persists data when saved', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();
    $localization->set('seo_title', 'Persisted Title');
    $localization->save();

    clearStache();

    $freshLocalization = Seo::find('collections::articles')->inDefaultSite();

    expect($freshLocalization->get('seo_title'))->toBe('Persisted Title');
});

it('can delete the localization', function () {
    Event::fake([SeoSetLocalizationDeleted::class]);

    $localization = Seo::find('collections::articles')->inDefaultSite();
    $localization->save();

    $result = $localization->delete();

    expect($result)->toBeTrue();

    Event::assertDispatched(SeoSetLocalizationDeleted::class, function ($event) {
        return $event->localization->id() === 'collections::articles::english';
    });

    expect(SeoLocalization::find('collections::articles::english'))->toBeNull();
});

it('returns file data filtered to blueprint fields only', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    $localization->set('seo_title', 'Test Title');
    $localization->set('non_blueprint_field', 'Should be excluded');

    $fileData = $localization->fileData();

    expect($fileData)->toHaveKey('seo_title');
    expect($fileData)->not->toHaveKey('non_blueprint_field');
});

it('can get the origin localization when set', function () {
    $seoSet = Seo::find('collections::articles');

    /* Set the german localization to have english as its origin */
    $seoSet->config()->origins(['german' => 'english'])->save();

    $germanLocalization = $seoSet->in('german');

    expect($germanLocalization->origin())->toBeInstanceOf(Contract::class);
    expect($germanLocalization->origin()->locale())->toBe('english');
});

it('returns null for origin when not set', function () {
    $seoSet = Seo::find('collections::articles');

    $englishLocalization = $seoSet->inDefaultSite();

    expect($englishLocalization->origin())->toBeNull();
});

it('can get default values from the blueprint', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    $defaultValues = $localization->defaultValues();

    expect($defaultValues)->toBeInstanceOf(Collection::class);
});

it('returns a new augmented instance', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    $augmented = $localization->newAugmentedInstance();

    expect($augmented)->toBeInstanceOf(AugmentedSeoSetLocalization::class);
});

it('resolves GraphQL values for valid blueprint fields', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();
    $localization->set('seo_title', 'GraphQL Title');

    $value = $localization->resolveGqlValue('seo_title');

    expect($value)->toBe('GraphQL Title');
});

it('returns null for GraphQL values of non-blueprint fields', function () {
    $localization = Seo::find('collections::articles')->inDefaultSite();

    $value = $localization->resolveGqlValue('non_existent_field');

    expect($value)->toBeNull();
});

it('flushes blink cache when saved', function () {
    $seoSet = Seo::find('collections::articles');
    $localization = $seoSet->inDefaultSite();

    /* Trigger caching */
    $seoSet->config();
    $seoSet->localizations();

    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeTrue();

    $localization->save();

    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeFalse();
});

it('flushes blink cache when deleted', function () {
    $seoSet = Seo::find('collections::articles');
    $localization = $seoSet->inDefaultSite();
    $localization->save();

    /* Trigger caching */
    $seoSet->config();
    $seoSet->localizations();

    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeTrue();

    $localization->delete();

    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeFalse();
});

it('inherits values from origin when configured', function () {
    $seoSet = Seo::find('collections::articles');

    /* Save the english localization with a value */
    $seoSet->in('english')
        ->set('seo_title', 'English Title')
        ->save();

    /* Set the german localization to have english as its origin */
    $seoSet->config()->origins(['german' => 'english'])->save();

    clearStache();

    $germanLocalization = Seo::find('collections::articles')->in('german');

    /* The german localization should inherit the title from english via the origin */
    expect($germanLocalization->value('seo_title'))->toBe('English Title');
});
