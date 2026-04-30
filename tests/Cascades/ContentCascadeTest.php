<?php

use Aerni\AdvancedSeo\Cascades\ContentCascade;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Fields\LabeledValue;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german'])->saveQuietly();

    $entry = Entry::make()->collection('pages')->locale('english')->slug('about')->data(['title' => 'About']);
    $entry->save();

    $this->entry = $entry;
    $this->cascade = ContentCascade::from($entry);
});

it('returns the computed keys', function () {
    expect($this->cascade->computedKeys()->all())->toBe([
        'site_name',
        'title',
        'og_title',
        'og_image_preset',
        'twitter_image_preset',
        'twitter_handle',
        'indexing',
        'locale',
        'hreflang',
        'canonical',
        'og_url',
        'site_schema',
        'page_schema',
        'breadcrumbs',
    ]);
});

it('returns the title from cascade data', function () {
    $this->cascade->set('title', 'Custom SEO Title');

    expect($this->cascade->title())->toBe('Custom SEO Title');
});

it('falls back to model title when cascade title is null', function () {
    $entry = Entry::make()->collection('pages')->locale('english')->slug('contact')->data(['title' => 'About Us']);
    $entry->save();

    $cascade = ContentCascade::from($entry);

    expect($cascade->title())->toContain('About Us');
});

it('falls back to site name when cascade title is not set', function () {
    expect($this->cascade->title())->toBeString()->not->toBeEmpty();
});

it('returns the og title from cascade data', function () {
    $this->cascade->set('og_title', 'Custom OG Title');

    expect($this->cascade->ogTitle())->toBe('Custom OG Title');
});

it('falls back to the entry title when og title is null', function () {
    expect($this->cascade->ogTitle())->toBe('About');
});

it('returns the absolute url as og url', function () {
    expect($this->cascade->ogUrl())->toBe('https://example.com/about');
});

it('prepends @ to the twitter handle', function () {
    $this->cascade->set('twitter_handle', 'myhandle');

    expect($this->cascade->twitterHandle())->toBe('@myhandle');
});

it('does not double prepend @ to the twitter handle', function () {
    $this->cascade->set('twitter_handle', '@myhandle');

    expect($this->cascade->twitterHandle())->toBe('@myhandle');
});

it('returns null twitter handle when not set', function () {
    expect($this->cascade->twitterHandle())->toBeNull();
});

it('returns noindex when noindex is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', true);
    $this->cascade->set('nofollow', false);

    expect($this->cascade->indexing())->toBe('noindex');
});

it('returns nofollow when nofollow is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', true);

    expect($this->cascade->indexing())->toBe('nofollow');
});

it('returns both noindex and nofollow when both are true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', true);
    $this->cascade->set('nofollow', true);

    expect($this->cascade->indexing())->toBe('noindex, nofollow');
});

it('returns null indexing when neither noindex nor nofollow is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', false);

    expect($this->cascade->indexing())->toBeNull();
});

it('returns noindex and nofollow when crawling is not enabled for the environment', function () {
    config(['advanced-seo.crawling.environments' => ['production']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', false);

    expect($this->cascade->indexing())->toBe('noindex, nofollow');
});

it('returns noarchive when noarchive is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', false);
    $this->cascade->set('noarchive', true);

    expect($this->cascade->indexing())->toBe('noarchive');
});

it('returns nosnippet when nosnippet is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', false);
    $this->cascade->set('nosnippet', true);

    expect($this->cascade->indexing())->toBe('nosnippet');
});

it('returns noimageindex when noimageindex is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', false);
    $this->cascade->set('noimageindex', true);

    expect($this->cascade->indexing())->toBe('noimageindex');
});

it('returns combined directives when multiple are true and noindex is false', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', true);
    $this->cascade->set('noarchive', true);
    $this->cascade->set('nosnippet', false);
    $this->cascade->set('noimageindex', true);

    expect($this->cascade->indexing())->toBe('nofollow, noarchive, noimageindex');
});

it('suppresses noarchive, nosnippet, noimageindex when noindex is true', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('noindex', true);
    $this->cascade->set('nofollow', false);
    $this->cascade->set('noarchive', true);
    $this->cascade->set('nosnippet', true);
    $this->cascade->set('noimageindex', true);

    expect($this->cascade->indexing())->toBe('noindex');
});

it('suppresses noarchive, nosnippet, noimageindex when crawling is disabled', function () {
    config(['advanced-seo.crawling.environments' => ['production']]);

    $this->cascade->set('noindex', false);
    $this->cascade->set('nofollow', false);
    $this->cascade->set('noarchive', true);
    $this->cascade->set('nosnippet', true);
    $this->cascade->set('noimageindex', true);

    expect($this->cascade->indexing())->toBe('noindex, nofollow');
});

it('returns the locale from the entry site', function () {
    expect($this->cascade->locale())->toBe('en');
});

it('returns the locale for a german entry', function () {
    $localized = $this->entry->makeLocalization('german')->slug('ueber-uns')->data(['title' => 'Über uns']);
    $localized->save();

    $cascade = ContentCascade::from($localized);

    expect($cascade->locale())->toBe('de');
});

it('returns null hreflang when multisite is disabled', function () {
    config(['statamic.system.multisite' => false]);

    expect($this->cascade->hreflang())->toBeNull();
});

it('returns null canonical when not indexable', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $entry = Entry::make()->collection('pages')->locale('english')->slug('hidden')
        ->data(['title' => 'Hidden', 'seo_noindex' => true]);
    $entry->save();

    $cascade = ContentCascade::from($entry);

    expect($cascade->canonical())->toBeNull();
});

it('returns the default canonical url', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    expect($this->cascade->canonical())->toBe('https://example.com/about');
});

it('returns custom canonical url', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('canonical_type', new LabeledValue('custom', 'Custom'));
    $this->cascade->set('canonical_custom', 'https://other-site.com/page');

    expect($this->cascade->canonical())->toBe('https://other-site.com/page');
});

it('returns entry canonical url', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $other = Entry::make()->collection('pages')->locale('english')->slug('original');
    $other->save();

    $this->cascade->set('canonical_type', new LabeledValue('entry', 'Entry'));
    $this->cascade->set('canonical_entry', $other);

    expect($this->cascade->canonical())->toBe('https://example.com/original');
});

it('falls back to self-referencing canonical when entry is null', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('canonical_type', new LabeledValue('entry', 'Entry'));
    $this->cascade->set('canonical_entry', null);

    expect($this->cascade->canonical())->toBe('https://example.com/about');
});

it('falls back to self-referencing canonical when custom canonical is null', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $this->cascade->set('canonical_type', new LabeledValue('custom', 'Custom'));
    $this->cascade->set('canonical_custom', null);

    expect($this->cascade->canonical())->toBe('https://example.com/about');
});

it('returns null site schema when type is none', function () {
    $this->cascade->set('site_json_ld_type', new LabeledValue('none', 'None'));

    expect($this->cascade->siteSchema())->toBeNull();
});

it('returns null site schema when type is not set', function () {
    expect($this->cascade->siteSchema())->toBeNull();
});

it('returns custom site schema json', function () {
    $this->cascade->set('site_json_ld_type', new LabeledValue('custom', 'Custom'));
    $this->cascade->set('site_json_ld', '{"@type": "Organization"}');

    expect($this->cascade->siteSchema())->toBe('{"@type": "Organization"}');
});

it('returns organization schema json', function () {
    $this->cascade->set('site_json_ld_type', new LabeledValue('organization', 'Organization'));
    $this->cascade->set('organization_name', 'Acme Inc');

    $schema = json_decode($this->cascade->siteSchema(), true);

    expect($schema['@type'])->toBe('Organization')
        ->and($schema['name'])->toBe('Acme Inc')
        ->and($schema['url'])->toBe('https://example.com');
});

it('returns person schema json', function () {
    $this->cascade->set('site_json_ld_type', new LabeledValue('person', 'Person'));
    $this->cascade->set('person_name', 'John Doe');

    $schema = json_decode($this->cascade->siteSchema(), true);

    expect($schema['@type'])->toBe('Person')
        ->and($schema['name'])->toBe('John Doe')
        ->and($schema['url'])->toBe('https://example.com');
});

it('returns null breadcrumbs when disabled', function () {
    $this->cascade->set('use_breadcrumbs', false);

    expect($this->cascade->breadcrumbs())->toBeNull();
});

it('returns breadcrumbs json ld when enabled and not homepage', function () {
    $this->cascade->set('use_breadcrumbs', true);

    $breadcrumbs = json_decode($this->cascade->breadcrumbs(), true);

    expect($breadcrumbs['@context'])->toBe('https://schema.org')
        ->and($breadcrumbs['@type'])->toBe('BreadcrumbList')
        ->and($breadcrumbs['itemListElement'])->toBeArray();

    foreach ($breadcrumbs['itemListElement'] as $item) {
        expect($item)->not->toHaveKey('@context')
            ->and($item['@type'])->toBe('ListItem');
    }
});

it('returns null page schema when json ld is not set', function () {
    expect($this->cascade->pageSchema())->toBeNull();
});

it('returns values including computed values', function () {
    $values = $this->cascade->values();

    expect($values)->toHaveKeys(['title', 'og_title', 'locale', 'og_url']);
});

it('returns a value by key including computed values', function () {
    expect($this->cascade->value('locale'))->toBe('en')
        ->and($this->cascade->value('og_url'))->toBe('https://example.com/about');
});

it('sorts data keys alphabetically', function () {
    $keys = $this->cascade->data()->keys()->all();

    expect($keys)->toBe(collect($keys)->sort()->values()->all());
});

it('removes the seo prefix from data keys', function () {
    $keys = $this->cascade->data()->keys()->all();

    expect(collect($keys)->filter(fn ($key) => str_starts_with($key, 'seo_')))->toBeEmpty();
});

it('returns og image preset dimensions', function () {
    $preset = $this->cascade->ogImagePreset();

    expect($preset)->toHaveKeys(['width', 'height'])
        ->and($preset['width'])->toBeInt()
        ->and($preset['height'])->toBeInt();
});

it('returns twitter image preset dimensions', function () {
    $this->cascade->set('twitter_card', 'summary');

    $preset = $this->cascade->twitterImagePreset();

    expect($preset)->toHaveKeys(['width', 'height'])
        ->and($preset['width'])->toBeInt()
        ->and($preset['height'])->toBeInt();
});

it('forces noindex from site defaults regardless of entry-level noindex', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    Blink::flush();

    Seo::find('site::defaults')->in('english')->set('noindex', true)->save();

    $entry = Entry::make()->collection('pages')->locale('english')->slug('forced-noindex')
        ->data(['title' => 'Forced', 'seo_noindex' => false]);
    $entry->save();

    $cascade = ContentCascade::from($entry);

    expect($cascade->get('noindex'))->toBeTrue()
        ->and($cascade->indexing())->toContain('noindex')
        ->and($cascade->canonical())->toBeNull();
});

it('respects entry-level noindex when site default does not force it', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    Blink::flush();

    Seo::find('site::defaults')->in('english')->set('noindex', false)->save();

    $entry = Entry::make()->collection('pages')->locale('english')->slug('entry-noindex')
        ->data(['title' => 'Entry', 'seo_noindex' => true]);
    $entry->save();

    $cascade = ContentCascade::from($entry);

    expect($cascade->get('noindex'))->toBeTrue()
        ->and($cascade->indexing())->toContain('noindex');
});

it('allows indexing when neither site default nor entry sets noindex', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    Blink::flush();

    Seo::find('site::defaults')->in('english')->set('noindex', false)->save();

    $entry = Entry::make()->collection('pages')->locale('english')->slug('indexable')
        ->data(['title' => 'Indexable', 'seo_noindex' => false]);
    $entry->save();

    $cascade = ContentCascade::from($entry);

    expect($cascade->get('noindex'))->toBeFalse()
        ->and($cascade->indexing())->toBeNull();
});
