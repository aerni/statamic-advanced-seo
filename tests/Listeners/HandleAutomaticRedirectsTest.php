<?php

use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesRedirects::class);

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

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/old')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}")
            ->and($redirect->responseCode())->toBe(RedirectResponseCode::Permanent)
            ->and($redirect->enabled())->toBeTrue()
            ->and($redirect->origin())->toBe(RedirectOrigin::Automatic);
    });

    it('creates a redirect when the date changes the url', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

        $entry->date('2025-06-20')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/2024/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    // The Eloquent driver leaves initialPath() null but keeps getOriginal()
    // reliable. An entry with a synced original and no initial path reproduces
    // that condition without standing up the whole Eloquent entries driver.
    it('captures the old url via getOriginal when there is no initial path', function () {
        $entry = Entry::make()->id('abc')->collection('pages')->locale('default')->slug('old')->published(true);
        $entry->syncOriginal();
        $entry->slug('new');

        event(new EntrySaving($entry));
        event(new EntrySaved($entry));

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/old')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('entry::abc');
    });

    it('captures the old dated url via getOriginal when there is no initial path', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

        $entry = Entry::make()->id('xyz')->collection('posts')->locale('default')->slug('post')->date('2024-01-15')->published(true);
        $entry->syncOriginal();
        $entry->date('2025-06-20');

        event(new EntrySaving($entry));
        event(new EntrySaved($entry));

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/2024/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('entry::xyz');
    });

    it('creates a prefix-stripped redirect in the entry site on a multisite collection', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('pages')->routes('/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('pages')->locale('en')->slug('page'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('old'))->save();

        $fr->slug('new')->save();

        $redirect = Redirect::query()->where('site', 'fr')->where('source', '/old')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$fr->id()}")
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
    });

    it('creates nothing when the date changes but the url stays the same', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

        $entry->date('2024-03-20')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when saving without a url change', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->set('title', 'Changed')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when another published entry still owns the old url', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('shared'))->save();
        tap(Entry::make()->collection('pages')->locale('default')->slug('shared'))->save();

        $entry->slug('moved')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing for a new entry', function () {
        tap(Entry::make()->collection('pages')->locale('default')->slug('fresh'))->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when a draft entry slug changes', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old')->published(false))->save();

        $entry->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when a scheduled entry slug changes', function () {
        Collection::make('posts')->dated(true)->futureDateBehavior('private')->routes('/blog/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('old')->date('2999-01-01'))->save();

        $entry->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when the collection toggle is off', function () {
        Seo::find('collections::pages')->config()->set('redirects', false)->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing on the free edition', function () {
        useFreeEdition();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('leaves an existing redirect with the same source untouched', function () {
        Redirect::make()->source('/old')->destination('/custom')->site('default')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        $redirects = Redirect::query()->where('site', 'default')->where('source', '/old')->get();

        expect($redirects)->toHaveCount(1)
            ->and($redirects->first()->destination())->toBe('/custom');
    });

    it('leaves a manual redirect that referenced the old url untouched', function () {
        Redirect::make()->source('/ancient')->destination('/old')->site('default')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        $manual = Redirect::query()->where('site', 'default')->where('source', '/ancient')->first();

        expect($manual->destination())->toBe('/old');
    });

    it('deletes redirects that would shadow the new url', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('a'))->save();

        $entry->slug('b')->save();
        $entry->slug('a')->save();

        $sources = Redirect::query()->where('site', 'default')->get()->map->source();

        expect($sources->all())->toBe(['/b']);
    });

    it('deletes an automatic redirect shadowed by a newly created entry', function () {
        Redirect::make()->source('/about')->destination('/elsewhere')->site('default')->origin(RedirectOrigin::Automatic)->save();

        tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();

        expect(Redirect::query()->where('site', 'default')->where('source', '/about')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect shadowed by a newly created entry', function () {
        Redirect::make()->source('/about')->destination('/elsewhere')->site('default')->save();

        tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/about')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/elsewhere');
    });

    it('keeps an automatic redirect when the shadowing entry is a draft', function () {
        Redirect::make()->source('/about')->destination('/elsewhere')->site('default')->origin(RedirectOrigin::Automatic)->save();

        tap(Entry::make()->collection('pages')->locale('default')->slug('about')->published(false))->save();

        expect(Redirect::query()->where('site', 'default')->where('source', '/about')->first())->not->toBeNull();
    });

    it('deletes an automatic redirect pointing to a deleted entry', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirect::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->origin(RedirectOrigin::Automatic)->save();

        $entry->delete();

        expect(Redirect::query()->where('site', 'default')->where('source', '/old')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect pointing to a deleted entry', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirect::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->save();

        $entry->delete();

        expect(Redirect::query()->where('site', 'default')->where('source', '/old')->first())->not->toBeNull();
    });

    it('keeps a redirect whose source is the deleted entry url', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirect::make()->source('/target')->destination('/elsewhere')->site('default')->origin(RedirectOrigin::Automatic)->save();

        $entry->delete();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/target')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/elsewhere');
    });

    it('deletes nothing on the free edition when an entry is deleted', function () {
        useFreeEdition();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('target'))->save();
        Redirect::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->origin(RedirectOrigin::Automatic)->save();

        $entry->delete();

        expect(Redirect::query()->where('site', 'default')->where('source', '/old')->first())->not->toBeNull();
    });
});

describe('term redirects', function () {
    beforeEach(function () {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();
    });

    it('creates a permanent redirect when a term slug changes', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/tags/old')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/tags/new')
            ->and($redirect->responseCode())->toBe(RedirectResponseCode::Permanent)
            ->and($redirect->enabled())->toBeTrue()
            ->and($redirect->origin())->toBe(RedirectOrigin::Automatic);
    });

    it('creates nothing when saving without a slug change', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->data(['title' => 'Changed'])->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing for a new term', function () {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('fresh')->data(['title' => 'Fresh']))->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when the taxonomy toggle is off', function () {
        Seo::find('taxonomies::tags')->config()->set('redirects', false)->save();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing on the free edition', function () {
        useFreeEdition();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('leaves an existing redirect with the same source untouched', function () {
        Redirect::make()->source('/tags/old')->destination('/custom')->site('default')->save();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        $redirects = Redirect::query()->where('site', 'default')->where('source', '/tags/old')->get();

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

        $en = Redirect::query()->where('site', 'en')->where('source', '/topics/old')->first();
        $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/old')->first();

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

        expect(Redirect::query()->where('site', 'en')->where('source', '/topics/old')->first())->not->toBeNull()
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
    });

    it('deletes redirects that would shadow the new term url', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('a')->data(['title' => 'A']))->save();

        $term->slug('b')->save();
        $term->slug('a')->save();

        $sources = Redirect::query()->where('site', 'default')->get()->map->source();

        expect($sources->all())->toBe(['/tags/b']);
    });

    it('flattens an automatic chain by repointing older redirects to the newest url', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('a')->data(['title' => 'A']))->save();

        $term->slug('b')->save();
        $term->slug('c')->save();

        $redirects = Redirect::query()->where('site', 'default')->get()
            ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

        expect($redirects)->toHaveCount(2)
            ->and($redirects->get('/tags/a'))->toBe('/tags/c')
            ->and($redirects->get('/tags/b'))->toBe('/tags/c');
    });

    it('deletes an automatic redirect shadowed by a newly created term', function () {
        Redirect::make()->source('/tags/about')->destination('/elsewhere')->site('default')->origin(RedirectOrigin::Automatic)->save();

        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('about')->data(['title' => 'About']))->save();

        expect(Redirect::query()->where('site', 'default')->where('source', '/tags/about')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect shadowed by a newly created term', function () {
        Redirect::make()->source('/tags/about')->destination('/elsewhere')->site('default')->save();

        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('about')->data(['title' => 'About']))->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/tags/about')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/elsewhere');
    });

    it('deletes an automatic redirect pointing to a deleted term', function () {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
        Redirect::make()->source('/tags/old')->destination('/tags/target')->site('default')->origin(RedirectOrigin::Automatic)->save();

        Term::find('tags::target')->delete();

        expect(Redirect::query()->where('site', 'default')->where('source', '/tags/old')->get())->toHaveCount(0);
    });

    it('keeps a manual redirect pointing to a deleted term', function () {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
        Redirect::make()->source('/tags/old')->destination('/tags/target')->site('default')->save();

        Term::find('tags::target')->delete();

        expect(Redirect::query()->where('site', 'default')->where('source', '/tags/old')->first())->not->toBeNull();
    });

    it('deletes automatic redirects in every site when a multisite term is deleted', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('target')->data(['title' => 'Target'])->save();
        Term::find('topics::target')->in('fr')->data(['title' => 'Cible'])->save();

        Redirect::make()->source('/topics/old')->destination('/topics/target')->site('en')->origin(RedirectOrigin::Automatic)->save();
        Redirect::make()->source('/topics/old')->destination('/topics/target')->site('fr')->origin(RedirectOrigin::Automatic)->save();

        Term::find('topics::target')->delete();

        expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
    });
});
