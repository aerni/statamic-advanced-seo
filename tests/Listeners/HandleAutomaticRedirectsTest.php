<?php

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);
});

describe('entry redirects', function () {
    beforeEach(function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    });

    it('creates a permanent redirect pointing at the entry when the slug changes', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        $redirect = Redirects::query()->where('site', 'default')->where('source', '/old')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}")
            ->and($redirect->responseCode())->toBe(ResponseCode::Permanent)
            ->and($redirect->enabled())->toBeTrue()
            ->and($redirect->automatic())->toBeTrue();
    });

    it('creates a redirect when the date changes the url', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

        $entry->date('2025-06-20')->save();

        $redirect = Redirects::query()->where('site', 'default')->where('source', '/blog/2024/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates nothing when the date changes but the url stays the same', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

        $entry->date('2024-03-20')->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when saving without a url change', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->set('title', 'Changed')->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing for a new entry', function () {
        tap(Entry::make()->collection('pages')->locale('default')->slug('fresh'))->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when the collection toggle is off', function () {
        Seo::find('collections::pages')->config()->set('redirects', false)->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing on the free edition', function () {
        useFreeEdition();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('leaves an existing redirect with the same source untouched', function () {
        Redirects::make()->source('/old')->destination('/custom')->site('default')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        $redirects = Redirects::query()->where('site', 'default')->where('source', '/old')->get();

        expect($redirects)->toHaveCount(1)
            ->and($redirects->first()->destination())->toBe('/custom');
    });

    it('repoints path destinations that referenced the old url to the entry', function () {
        Redirects::make()->source('/ancient')->destination('/old')->site('default')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        $repointed = Redirects::query()->where('site', 'default')->where('source', '/ancient')->first();

        expect($repointed->destination())->toBe("entry::{$entry->id()}");
    });

    it('deletes redirects that would shadow the new url', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('a'))->save();

        $entry->slug('b')->save();
        $entry->slug('a')->save();

        $sources = Redirects::query()->where('site', 'default')->get()->map->source();

        expect($sources->all())->toBe(['/b']);
    });

    it('deletes an automatic redirect shadowed by a newly created entry', function () {
        Redirects::make()->source('/about')->destination('/elsewhere')->site('default')->automatic(true)->save();

        tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();

        expect(Redirects::query()->where('site', 'default')->where('source', '/about')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect shadowed by a newly created entry', function () {
        Redirects::make()->source('/about')->destination('/elsewhere')->site('default')->save();

        tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();

        $redirect = Redirects::query()->where('site', 'default')->where('source', '/about')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/elsewhere');
    });

    it('keeps an automatic redirect when the shadowing entry is a draft', function () {
        Redirects::make()->source('/about')->destination('/elsewhere')->site('default')->automatic(true)->save();

        tap(Entry::make()->collection('pages')->locale('default')->slug('about')->published(false))->save();

        expect(Redirects::query()->where('site', 'default')->where('source', '/about')->first())->not->toBeNull();
    });

    it('deletes an automatic redirect pointing to a deleted entry', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirects::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->automatic(true)->save();

        $entry->delete();

        expect(Redirects::query()->where('site', 'default')->where('source', '/old')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect pointing to a deleted entry', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirects::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->save();

        $entry->delete();

        expect(Redirects::query()->where('site', 'default')->where('source', '/old')->first())->not->toBeNull();
    });

    it('keeps a redirect whose source is the deleted entry url', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirects::make()->source('/target')->destination('/elsewhere')->site('default')->automatic(true)->save();

        $entry->delete();

        $redirect = Redirects::query()->where('site', 'default')->where('source', '/target')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/elsewhere');
    });

    it('deletes nothing on the free edition when an entry is deleted', function () {
        useFreeEdition();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirects::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->automatic(true)->save();

        $entry->delete();

        expect(Redirects::query()->where('site', 'default')->where('source', '/old')->first())->not->toBeNull();
    });
});

describe('term redirects', function () {
    beforeEach(function () {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();
    });

    it('creates a permanent redirect when a term slug changes', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        $redirect = Redirects::query()->where('site', 'default')->where('source', '/tags/old')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/tags/new')
            ->and($redirect->responseCode())->toBe(ResponseCode::Permanent)
            ->and($redirect->enabled())->toBeTrue()
            ->and($redirect->automatic())->toBeTrue();
    });

    it('creates nothing when saving without a slug change', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->data(['title' => 'Changed'])->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing for a new term', function () {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('fresh')->data(['title' => 'Fresh']))->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when the taxonomy toggle is off', function () {
        Seo::find('taxonomies::tags')->config()->set('redirects', false)->save();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing on the free edition', function () {
        useFreeEdition();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('leaves an existing redirect with the same source untouched', function () {
        Redirects::make()->source('/tags/old')->destination('/custom')->site('default')->save();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        $redirects = Redirects::query()->where('site', 'default')->where('source', '/tags/old')->get();

        expect($redirects)->toHaveCount(1)
            ->and($redirects->first()->destination())->toBe('/custom');
    });

    it('creates a prefix-stripped redirect per site when a shared slug changes', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();
        // French inherits the base slug (title only, no slug override).
        Term::find('topics::old')->in('fr')->data(['title' => 'Vieux'])->save();

        Term::find('topics::old')->in('en')->slug('new')->save();

        $en = Redirects::query()->where('site', 'en')->where('source', '/topics/old')->first();
        $fr = Redirects::query()->where('site', 'fr')->where('source', '/topics/old')->first();

        expect($en?->destination())->toBe('/topics/new')
            ->and($fr)->not->toBeNull()
            ->and($fr->destination())->toBe('/topics/new');
    });

    it('skips a localization with its own overridden slug', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();
        // French overrides its own slug, so a base-slug change won't affect its URL.
        Term::find('topics::old')->in('fr')->data(['title' => 'Vieux'])->slug('vieux')->save();

        Term::find('topics::old')->in('en')->slug('new')->save();

        expect(Redirects::query()->where('site', 'en')->where('source', '/topics/old')->first())->not->toBeNull()
            ->and(Redirects::query()->where('site', 'fr')->get())->toHaveCount(0);
    });

    it('deletes redirects that would shadow the new term url', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('a')->data(['title' => 'A']))->save();

        $term->slug('b')->save();
        $term->slug('a')->save();

        $sources = Redirects::query()->where('site', 'default')->get()->map->source();

        expect($sources->all())->toBe(['/tags/b']);
    });

    it('deletes an automatic redirect shadowed by a newly created term', function () {
        Redirects::make()->source('/tags/about')->destination('/elsewhere')->site('default')->automatic(true)->save();

        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('about')->data(['title' => 'About']))->save();

        expect(Redirects::query()->where('site', 'default')->where('source', '/tags/about')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect shadowed by a newly created term', function () {
        Redirects::make()->source('/tags/about')->destination('/elsewhere')->site('default')->save();

        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('about')->data(['title' => 'About']))->save();

        $redirect = Redirects::query()->where('site', 'default')->where('source', '/tags/about')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/elsewhere');
    });

    it('deletes an automatic redirect pointing to a deleted term', function () {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
        Redirects::make()->source('/tags/old')->destination('/tags/target')->site('default')->automatic(true)->save();

        Term::find('tags::target')->delete();

        expect(Redirects::query()->where('site', 'default')->where('source', '/tags/old')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect pointing to a deleted term', function () {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
        Redirects::make()->source('/tags/old')->destination('/tags/target')->site('default')->save();

        Term::find('tags::target')->delete();

        expect(Redirects::query()->where('site', 'default')->where('source', '/tags/old')->first())->not->toBeNull();
    });
});
