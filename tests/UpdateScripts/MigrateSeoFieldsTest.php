<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateSeoFields;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

function runMigrateSeoFieldsScript(): void
{
    (new MigrateSeoFields)->run();
}

it('migrates @auto values to @default', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->set('seo_title', '@auto')
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_title' => '@auto'])
        ->save();

    runMigrateSeoFieldsScript();

    expect(Entry::all()->first()->get('seo_title'))->toBe('@default');
    expect(Term::find('tags::test-tag')->in('english')->get('seo_title'))->toBe('@default');
});

it('migrates @null values to @default', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->set('seo_title', '@null')
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_title' => '@null'])
        ->save();

    runMigrateSeoFieldsScript();

    expect(Entry::all()->first()->get('seo_title'))->toBe('@default');
    expect(Term::find('tags::test-tag')->in('english')->get('seo_title'))->toBe('@default');
});

it('migrates @field references to Antlers syntax', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->set('seo_title', '@field:title')
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_title' => '@field:title'])
        ->save();

    runMigrateSeoFieldsScript();

    expect(Entry::all()->first()->get('seo_title'))->toBe('{{ title }}');
    expect(Term::find('tags::test-tag')->in('english')->get('seo_title'))->toBe('{{ title }}');
});

it('removes legacy sentinels and migrates @field references on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_description' => '@auto',
            'seo_og_title' => '@null',
            'seo_og_description' => '@field:seo_title',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_description'))->toBeNull();
    expect($localization->get('seo_og_title'))->toBeNull();
    expect($localization->get('seo_og_description'))->toBe('{{ seo_title }}');
});

it('does not modify non-seo fields', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'content' => 'Some content',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('title'))->toBe('Test Page');
    expect($entry->get('content'))->toBe('Some content');
});

it('removes twitter fields from entries and terms', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'Twitter Title',
            'seo_twitter_description' => 'Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'title' => 'Test Tag',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'Twitter Title',
            'seo_twitter_description' => 'Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();
    $term = Term::find('tags::test-tag')->in('english');

    expect($entry->get('title'))->toBe('Test Page');
    expect($entry->get('seo_twitter_card'))->toBeNull();
    expect($entry->get('seo_twitter_title'))->toBeNull();
    expect($entry->get('seo_twitter_description'))->toBeNull();
    expect($entry->get('seo_twitter_summary_image'))->toBeNull();
    expect($entry->get('seo_twitter_summary_large_image'))->toBeNull();

    expect($term->get('title'))->toBe('Test Tag');
    expect($term->get('seo_twitter_card'))->toBeNull();
    expect($term->get('seo_twitter_title'))->toBeNull();
    expect($term->get('seo_twitter_description'))->toBeNull();
    expect($term->get('seo_twitter_summary_image'))->toBeNull();
    expect($term->get('seo_twitter_summary_large_image'))->toBeNull();
});

it('removes twitter fields from seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'Twitter Title',
            'seo_twitter_description' => 'Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->has('seo_twitter_card'))->toBeFalse();
    expect($localization->has('seo_twitter_title'))->toBeFalse();
    expect($localization->has('seo_twitter_description'))->toBeFalse();
    expect($localization->has('seo_twitter_summary_image'))->toBeFalse();
    expect($localization->has('seo_twitter_summary_large_image'))->toBeFalse();
});

it('removes twitter image fields from site defaults', function () {
    Seo::find('site::defaults')
        ->inDefaultSite()
        ->data([
            'twitter_summary_image' => 'social_images/twitter.jpg',
            'twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('site::defaults')->inDefaultSite();

    expect($localization->has('twitter_summary_image'))->toBeFalse();
    expect($localization->has('twitter_summary_large_image'))->toBeFalse();
});

it('removes nofollow field from site defaults', function () {
    Seo::find('site::defaults')
        ->inDefaultSite()
        ->data([
            'noindex' => true,
            'nofollow' => true,
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('site::defaults')->inDefaultSite();

    expect($localization->has('noindex'))->toBeTrue();
    expect($localization->has('nofollow'))->toBeFalse();
});

it('removes analytics toggles from site defaults', function () {
    Seo::find('site::defaults')
        ->inDefaultSite()
        ->data([
            'use_fathom' => true,
            'fathom_id' => 'ABC123',
            'use_cloudflare_web_analytics' => true,
            'use_google_tag_manager' => true,
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('site::defaults')->inDefaultSite();

    expect($localization->has('use_fathom'))->toBeFalse();
    expect($localization->has('use_cloudflare_web_analytics'))->toBeFalse();
    expect($localization->has('use_google_tag_manager'))->toBeFalse();

    expect($localization->get('fathom_id'))->toBe('ABC123');
});

it('renames analytics fields on site defaults', function () {
    Seo::find('site::defaults')
        ->inDefaultSite()
        ->data([
            'cloudflare_web_analytics' => 'token',
            'google_tag_manager' => 'GTM-XYZ',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('site::defaults')->inDefaultSite();

    expect($localization->has('cloudflare_web_analytics'))->toBeFalse();
    expect($localization->has('google_tag_manager'))->toBeFalse();

    expect($localization->get('cloudflare_beacon_token'))->toBe('token');
    expect($localization->get('gtm_container_id'))->toBe('GTM-XYZ');
});

it('removes sitemap fields from entries and terms', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'seo_sitemap_priority' => '0.8',
            'seo_sitemap_change_frequency' => 'weekly',
        ])
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'title' => 'Test Tag',
            'seo_sitemap_priority' => '0.5',
            'seo_sitemap_change_frequency' => 'daily',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();
    $term = Term::find('tags::test-tag')->in('english');

    expect($entry->get('title'))->toBe('Test Page');
    expect($entry->get('seo_sitemap_priority'))->toBeNull();
    expect($entry->get('seo_sitemap_change_frequency'))->toBeNull();

    expect($term->get('title'))->toBe('Test Tag');
    expect($term->get('seo_sitemap_priority'))->toBeNull();
    expect($term->get('seo_sitemap_change_frequency'))->toBeNull();
});

it('removes sitemap fields from seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_sitemap_priority' => '0.5',
            'seo_sitemap_change_frequency' => 'daily',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->has('seo_sitemap_priority'))->toBeFalse();
    expect($localization->has('seo_sitemap_change_frequency'))->toBeFalse();
});

it('migrates @default canonical values to removal on entries and terms', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'seo_canonical_type' => '@default',
            'seo_canonical_entry' => '@default',
            'seo_canonical_custom' => '@default',
        ])
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'title' => 'Test Tag',
            'seo_canonical_type' => '@default',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();
    $term = Term::find('tags::test-tag')->in('english');

    expect($entry->get('title'))->toBe('Test Page');
    expect($entry->get('seo_canonical_type'))->toBeNull();
    expect($entry->get('seo_canonical_entry'))->toBeNull();
    expect($entry->get('seo_canonical_custom'))->toBeNull();

    expect($term->get('title'))->toBe('Test Tag');
    expect($term->get('seo_canonical_type'))->toBeNull();
});

it('preserves custom canonical values on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'seo_canonical_type' => 'custom',
            'seo_canonical_custom' => 'https://example.com/original',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_canonical_type'))->toBe('custom');
    expect($entry->get('seo_canonical_custom'))->toBe('https://example.com/original');
});

it('renames other canonical type to entry', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'seo_canonical_type' => 'other',
            'seo_canonical_entry' => 'some-entry-id',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_canonical_type'))->toBe('entry');
    expect($entry->get('seo_canonical_entry'))->toBe('some-entry-id');
});

it('removes canonical fields from seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_canonical_type' => 'current',
            'seo_canonical_entry' => null,
            'seo_canonical_custom' => null,
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->has('seo_canonical_type'))->toBeFalse();
    expect($localization->has('seo_canonical_entry'))->toBeFalse();
    expect($localization->has('seo_canonical_custom'))->toBeFalse();
});

it('renames title_separator to separator in site defaults', function () {
    Seo::find('site::defaults')->in('english')->data(['title_separator' => '/'])->save();
    Seo::find('site::defaults')->in('german')->data(['title_separator' => '–'])->save();

    runMigrateSeoFieldsScript();

    expect(Seo::find('site::defaults')->in('english')->get('separator'))->toBe('/');
    expect(Seo::find('site::defaults')->in('english')->has('title_separator'))->toBeFalse();

    expect(Seo::find('site::defaults')->in('german')->get('separator'))->toBe('–');
    expect(Seo::find('site::defaults')->in('german')->has('title_separator'))->toBeFalse();
});

it('composes seo_title with site name position end on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_title' => 'Custom Title',
            'seo_site_name_position' => 'end',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('Custom Title {{ separator }} {{ site_name }}');
    expect($localization->has('seo_site_name_position'))->toBeFalse();
});

it('composes seo_title with site name position start on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_title' => 'Custom Title',
            'seo_site_name_position' => 'start',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('{{ site_name }} {{ separator }} Custom Title');
    expect($localization->has('seo_site_name_position'))->toBeFalse();
});

it('composes seo_title with site name position disabled on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_title' => 'Custom Title',
            'seo_site_name_position' => 'disabled',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('Custom Title');
    expect($localization->has('seo_site_name_position'))->toBeFalse();
});

it('composes seo_title with default title and position on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data(['seo_site_name_position' => 'start'])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('{{ site_name }} {{ separator }} {{ title }}');
    expect($localization->has('seo_site_name_position'))->toBeFalse();
});

it('does not modify seo_title when both title and position are absent on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data(['seo_description' => 'A description'])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_title'))->toBeNull();
    expect($localization->get('seo_description'))->toBe('A description');
});

it('cleans up position field when both title and position are default on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['seo_site_name_position' => '@default'])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBeNull();
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('resolves @default position from cascade on entries', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data(['seo_site_name_position' => 'start'])
        ->save();

    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['seo_site_name_position' => '@default'])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('{{ site_name }} {{ separator }} {{ title }}');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('resolves @default position from cascade on terms', function () {
    Seo::find('taxonomies::tags')
        ->inDefaultSite()
        ->data(['seo_site_name_position' => 'end'])
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_site_name_position' => '@default'])
        ->save();

    runMigrateSeoFieldsScript();

    $term = Term::find('tags::test-tag')->in('english');

    expect($term->get('seo_title'))->toBe('{{ title }} {{ separator }} {{ site_name }}');
    expect($term->has('seo_site_name_position'))->toBeFalse();
});

it('composes title with custom position and default title on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['seo_site_name_position' => 'start'])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('{{ site_name }} {{ separator }} {{ title }}');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('uses default title template when position is disabled on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['seo_site_name_position' => 'disabled'])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('{{ title }}');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('composes title when position is end and title is default on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['seo_site_name_position' => 'end'])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('{{ title }} {{ separator }} {{ site_name }}');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('composes title with custom title and position end on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'seo_title' => 'My Custom Title',
            'seo_site_name_position' => 'end',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('My Custom Title {{ separator }} {{ site_name }}');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('composes title with custom title and position start on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'seo_title' => 'My Custom Title',
            'seo_site_name_position' => 'start',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('{{ site_name }} {{ separator }} My Custom Title');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('composes title with custom title and position disabled on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'seo_title' => 'My Custom Title',
            'seo_site_name_position' => 'disabled',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('My Custom Title');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('does not compose title when position is not set on entries', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['seo_title' => 'My Custom Title'])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('seo_title'))->toBe('My Custom Title');
    expect($entry->has('seo_site_name_position'))->toBeFalse();
});

it('composes title with position on terms', function () {
    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'seo_title' => 'Tag Title',
            'seo_site_name_position' => 'start',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $term = Term::find('tags::test-tag')->in('english');

    expect($term->get('seo_title'))->toBe('{{ site_name }} {{ separator }} Tag Title');
    expect($term->has('seo_site_name_position'))->toBeFalse();
});
