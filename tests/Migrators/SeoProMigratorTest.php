<?php

use Aerni\AdvancedSeo\Migrators\SeoProMigrator;
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

it('migrates SEO Pro entry fields to Advanced SEO', function () {
    Entry::make()->collection('pages')->locale('english')->slug('about')->data([
        'title' => 'About',
        'body' => 'Some content',
        'seo' => [
            'title' => 'About | My Site',
            'description' => 'An about page.',
            'canonical_url' => 'https://example.com/about',
            'og_title' => 'About Us',
        ],
    ])->save();

    SeoProMigrator::run();

    $migrated = Entry::query()->where('slug', 'about')->first();

    expect($migrated->get('title'))->toBe('About')
        ->and($migrated->get('body'))->toBe('Some content')
        ->and($migrated->get('seo_title'))->toBe('About | My Site')
        ->and($migrated->get('seo_description'))->toBe('An about page.')
        ->and($migrated->get('seo_canonical_custom'))->toBe('https://example.com/about')
        ->and($migrated->get('seo_canonical_type'))->toBe('custom')
        ->and($migrated->get('seo_og_title'))->toBe('About Us')
        ->and($migrated->get('seo'))->toBeNull();
});

it('transforms robots array into noindex and nofollow', function () {
    Entry::make()->collection('pages')->locale('english')->slug('hidden')->data([
        'title' => 'Hidden',
        'seo' => [
            'robots' => ['noindex', 'nofollow'],
        ],
    ])->save();

    SeoProMigrator::run();

    $migrated = Entry::query()->where('slug', 'hidden')->first();

    expect($migrated->get('seo_noindex'))->toBeTrue()
        ->and($migrated->get('seo_nofollow'))->toBeTrue();
});

it('transforms legacy robots_indexing and robots_following into noindex and nofollow', function () {
    Entry::make()->collection('pages')->locale('english')->slug('legacy-robots')->data([
        'title' => 'Legacy',
        'seo' => [
            'robots_indexing' => 'noindex',
            'robots_following' => 'nofollow',
        ],
    ])->save();

    SeoProMigrator::run();

    $migrated = Entry::query()->where('slug', 'legacy-robots')->first();

    expect($migrated->get('seo_noindex'))->toBeTrue()
        ->and($migrated->get('seo_nofollow'))->toBeTrue();
});

it('rewrites @seo: token references to Antlers syntax', function () {
    Entry::make()->collection('pages')->locale('english')->slug('tokens')->data([
        'title' => 'Tokens',
        'seo' => [
            'title' => '@seo:site_name | @seo:separator',
        ],
    ])->save();

    SeoProMigrator::run();

    $migrated = Entry::query()->where('slug', 'tokens')->first();

    expect($migrated->get('seo_title'))->toBe('{{ site_name }} | {{ separator }}');
});

it('drops SEO Pro fields that have no Advanced SEO counterpart', function () {
    Entry::make()->collection('pages')->locale('english')->slug('extras')->data([
        'title' => 'Extras',
        'seo' => [
            'title' => 'Extras',
            'priority' => '0.8',
            'change_frequency' => 'weekly',
            'site_name' => 'Manually Set',
            'twitter_handle' => '@ignore',
        ],
    ])->save();

    SeoProMigrator::run();

    $migrated = Entry::query()->where('slug', 'extras')->first();

    expect($migrated->get('seo_title'))->toBe('Extras')
        ->and($migrated->get('priority'))->toBeNull()
        ->and($migrated->get('change_frequency'))->toBeNull()
        ->and($migrated->get('site_name'))->toBeNull()
        ->and($migrated->get('twitter_handle'))->toBeNull();
});

it('migrates term data across all locales of a multi-site taxonomy', function () {
    $term = Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data([
        'title' => 'PHP',
        'seo' => [
            'title' => 'PHP Articles',
            'description' => 'Articles about PHP.',
        ],
    ]);
    $term->save();

    $term->in('german')->data([
        'title' => 'PHP',
        'seo' => [
            'title' => 'PHP Artikel',
            'description' => 'Artikel über PHP.',
        ],
    ])->save();

    SeoProMigrator::run();

    $english = Term::query()->where('slug', 'php')->first()->in('english');
    $german = Term::query()->where('slug', 'php')->first()->in('german');

    expect($english->get('seo_title'))->toBe('PHP Articles')
        ->and($english->get('seo_description'))->toBe('Articles about PHP.')
        ->and($english->get('seo'))->toBeNull()
        ->and($german->get('seo_title'))->toBe('PHP Artikel')
        ->and($german->get('seo_description'))->toBe('Artikel über PHP.')
        ->and($german->get('seo'))->toBeNull();
});
