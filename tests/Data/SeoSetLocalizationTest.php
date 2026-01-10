<?php

use Statamic\Facades\Site;
use Statamic\Facades\Blink;
use Statamic\Fields\Blueprint;
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


it('can get the blueprint', function () {
    $blueprint = Seo::find('collections::articles')->inDefaultSite()->blueprint();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('content_localization');

    $blueprint = Seo::find('taxonomies::tags')->inDefaultSite()->blueprint();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('content_localization');

    $blueprint = Seo::find('site::general')->inDefaultSite()->blueprint();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('general');
});
