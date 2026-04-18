<?php

use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

it('migrates Aardvark entry fields to Advanced SEO', function () {
    Entry::make()->collection('pages')->locale('english')->slug('about')->data([
        'title' => 'About',
        'body' => 'Some content',
        'meta_title' => 'About | My Site',
        'meta_description' => 'An about page.',
        'og_title' => 'About Us',
        'og_description' => 'Description for sharing.',
        'og_image' => 'assets::image.jpg',
        'no_index_page' => true,
        'no_follow_links' => true,
        'canonical_url' => 'https://example.com/about',
    ])->save();

    AardvarkSeoMigrator::run();

    $migrated = Entry::query()->where('slug', 'about')->first();

    expect($migrated->get('title'))->toBe('About')
        ->and($migrated->get('body'))->toBe('Some content')
        ->and($migrated->get('seo_title'))->toBe('About | My Site')
        ->and($migrated->get('seo_description'))->toBe('An about page.')
        ->and($migrated->get('seo_og_title'))->toBe('About Us')
        ->and($migrated->get('seo_og_description'))->toBe('Description for sharing.')
        ->and($migrated->get('seo_og_image'))->toBe('assets::image.jpg')
        ->and($migrated->get('seo_noindex'))->toBeTrue()
        ->and($migrated->get('seo_nofollow'))->toBeTrue()
        ->and($migrated->get('seo_canonical_custom'))->toBe('https://example.com/about')
        ->and($migrated->get('seo_canonical_type'))->toBe('custom')
        ->and($migrated->get('meta_title'))->toBeNull()
        ->and($migrated->get('meta_description'))->toBeNull();
});

it('strips script wrapper from JSON-LD schema', function () {
    $raw = <<<'HTML'
<script type="application/ld+json">
{"@type":"Organization","name":"Acme"}
</script>
HTML;

    Entry::make()->collection('pages')->locale('english')->slug('schema')->data([
        'title' => 'Schema',
        'schema_objects' => $raw,
    ])->save();

    AardvarkSeoMigrator::run();

    $migrated = Entry::query()->where('slug', 'schema')->first();

    expect($migrated->get('seo_json_ld'))->toBe('{"@type":"Organization","name":"Acme"}'."\n")
        ->and($migrated->get('schema_objects'))->toBeNull();
});

it('drops Aardvark fields that have no Advanced SEO counterpart', function () {
    Entry::make()->collection('pages')->locale('english')->slug('extras')->data([
        'title' => 'Extras',
        'meta_title' => 'Extras',
        'sitemap_priority' => '0.8',
        'sitemap_changefreq' => 'weekly',
        'twitter_title' => 'ignored',
        'meta_keywords' => 'seo, statamic',
    ])->save();

    AardvarkSeoMigrator::run();

    $migrated = Entry::query()->where('slug', 'extras')->first();

    expect($migrated->get('seo_title'))->toBe('Extras')
        ->and($migrated->get('sitemap_priority'))->toBeNull()
        ->and($migrated->get('sitemap_changefreq'))->toBeNull()
        ->and($migrated->get('twitter_title'))->toBeNull()
        ->and($migrated->get('meta_keywords'))->toBeNull();
});

it('migrates term data across all locales of a multi-site taxonomy', function () {
    $term = Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data([
        'title' => 'PHP',
        'meta_title' => 'PHP Articles',
        'meta_description' => 'Articles about PHP.',
    ]);
    $term->save();

    $term->in('german')->data([
        'title' => 'PHP',
        'meta_title' => 'PHP Artikel',
        'meta_description' => 'Artikel über PHP.',
    ])->save();

    AardvarkSeoMigrator::run();

    $english = Term::query()->where('slug', 'php')->first()->in('english');
    $german = Term::query()->where('slug', 'php')->first()->in('german');

    expect($english->get('seo_title'))->toBe('PHP Articles')
        ->and($english->get('seo_description'))->toBe('Articles about PHP.')
        ->and($english->get('meta_title'))->toBeNull()
        ->and($german->get('seo_title'))->toBe('PHP Artikel')
        ->and($german->get('seo_description'))->toBe('Artikel über PHP.')
        ->and($german->get('meta_title'))->toBeNull();
});
