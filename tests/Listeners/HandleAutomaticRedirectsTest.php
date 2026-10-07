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
use Statamic\Facades\Stache;
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

    it('creates a redirect when a route token field changes the url', function () {
        Collection::find('pages')->routes('/{category}/{slug}')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('category', 'news'))->save();

        $entry->set('category', 'updates')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates nothing when a field changes that is not in the route', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('category', 'news'))->save();

        $entry->set('category', 'updates')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
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

    it('captures the old route token url via getOriginal when there is no initial path', function () {
        Collection::find('pages')->routes('/{category}/{slug}')->save();

        $entry = Entry::make()->id('abc')->collection('pages')->locale('default')->slug('post')->set('category', 'news')->published(true);
        $entry->syncOriginal();
        $entry->set('category', 'updates');

        event(new EntrySaving($entry));
        event(new EntrySaved($entry));

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('entry::abc');
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

    it('creates a redirect when a timed date changes the day', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{month}/{day}/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15-1430'))->save();

        $entry->date('2024-06-20-0900')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/2024/01/15/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates nothing when only the time changes', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{month}/{day}/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15-1430'))->save();

        $entry->date('2024-01-15-1500')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
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

    it('creates nothing when a draft entry is published and its slug changes in the same save', function () {
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old')->published(false))->save();

        $entry->slug('new')->published(true)->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when a scheduled entry is published and its slug changes in the same save', function () {
        Collection::make('posts')->dated(true)->futureDateBehavior('private')->routes('/blog/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('old')->date('2999-01-01'))->save();

        $entry->slug('new')->date('2024-01-15')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when an expired entry is published and its slug changes in the same save', function () {
        Collection::make('posts')->dated(true)->pastDateBehavior('private')->routes('/blog/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('old')->date('2020-01-01'))->save();

        $entry->slug('new')->date('2024-06-20')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates a redirect when an inherited localization route field changes', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $en->set('category', 'updates')->save();

        $origin = Redirect::query()->where('site', 'en')->where('source', '/news/post')->first();
        $localization = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();

        expect($origin?->destination())->toBe("entry::{$en->id()}")
            ->and($localization)->not->toBeNull()
            ->and($localization->destination())->toBe("entry::{$fr->id()}");
    });

    it('creates nothing for a localization that overrides the changed route field', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        tap($en->makeLocalization('fr')->slug('article')->set('category', 'actualites'))->save();

        forgetRedirects();

        $en->set('category', 'updates')->save();

        expect(Redirect::query()->where('site', 'en')->where('source', '/news/post')->first())->not->toBeNull()
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
    });

    it('creates a redirect when a localization route uses a field the origin route does not', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes([
            'en' => '/{slug}',
            'fr' => '/{category}/{slug}',
        ])->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $en->set('category', 'updates')->save();

        $localization = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();

        expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
            ->and($localization)->not->toBeNull()
            ->and($localization->destination())->toBe("entry::{$fr->id()}");
    });

    it('creates nothing when a localization overrides a field that only its route uses', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes([
            'en' => '/{slug}',
            'fr' => '/{category}/{slug}',
        ])->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        tap($en->makeLocalization('fr')->slug('article')->set('category', 'actualites'))->save();

        forgetRedirects();

        $en->set('category', 'updates')->save();

        expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
    });

    it('creates a redirect for a localized child page when an inherited route field changes', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('guides')
            ->routes('/{category}/{parent_uri}/{slug}')
            ->sites(['en', 'fr'])
            ->structureContents(['root' => false])
            ->saveQuietly();

        $about = tap(Entry::make()->collection('guides')->locale('en')->slug('about')->set('category', 'news'))->save();
        $team = tap(Entry::make()->collection('guides')->locale('en')->slug('team'))->save();

        Collection::find('guides')->structure()->in('en')->tree([
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ])->save();

        $aboutFr = tap($about->makeLocalization('fr')->slug('a-propos'))->save();
        $teamFr = tap($team->makeLocalization('fr')->slug('equipe'))->save();

        forgetRedirects();

        $about->set('category', 'updates')->save();

        $redirects = Redirect::query()->where('site', 'fr')->get()
            ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

        expect($redirects->get('/news/a-propos'))->toBe("entry::{$aboutFr->id()}")
            ->and($redirects->get('/news/a-propos/equipe'))->toBe("entry::{$teamFr->id()}");
    });

    it('creates a redirect when an inherited localization date changes the url', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->dated(true)->routes('/{year}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2024-01-15'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $en->date('2025-06-20')->save();

        $origin = Redirect::query()->where('site', 'en')->where('source', '/2024/post')->first();
        $localization = Redirect::query()->where('site', 'fr')->where('source', '/2024/article')->first();

        expect($origin?->destination())->toBe("entry::{$en->id()}")
            ->and($localization)->not->toBeNull()
            ->and($localization->destination())->toBe("entry::{$fr->id()}");
    });

    it('creates nothing for a localization that has its own date', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->dated(true)->routes('/{year}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2024-01-15'))->save();
        tap($en->makeLocalization('fr')->slug('article')->date('2019-05-05'))->save();

        forgetRedirects();

        $en->date('2025-06-20')->save();

        expect(Redirect::query()->where('site', 'en')->where('source', '/2024/post')->first())->not->toBeNull()
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
    });

    it('creates a redirect for a localized child page when an inherited date changes the url', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('guides')
            ->dated(true)
            ->routes('/{year}/{parent_uri}/{slug}')
            ->sites(['en', 'fr'])
            ->structureContents(['root' => false])
            ->saveQuietly();

        $about = tap(Entry::make()->collection('guides')->locale('en')->slug('about')->date('2024-01-15'))->save();
        $team = tap(Entry::make()->collection('guides')->locale('en')->slug('team')->date('2024-06-01'))->save();

        Collection::find('guides')->structure()->in('en')->tree([
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ])->save();

        $aboutFr = tap($about->makeLocalization('fr')->slug('a-propos'))->save();
        $teamFr = tap($team->makeLocalization('fr')->slug('equipe'))->save();

        forgetRedirects();

        $about->date('2025-06-20')->save();

        $redirects = Redirect::query()->where('site', 'fr')->get()
            ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

        expect($redirects->get('/2024/a-propos'))->toBe("entry::{$aboutFr->id()}")
            ->and($redirects->get('/2024/2024/a-propos/equipe'))->toBe("entry::{$teamFr->id()}");
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

describe('structure redirects', function () {
    beforeEach(function () {
        Collection::make('pages')
            ->routes('/{parent_uri}/{slug}')
            ->sites(['default'])
            ->structureContents(['root' => true])
            ->save();
    });

    it('creates a redirect when an entry moves in the tree', function () {
        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/about/team')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$team->id()}")
            ->and($redirect->responseCode())->toBe(RedirectResponseCode::Permanent)
            ->and($redirect->origin())->toBe(RedirectOrigin::Automatic)
            ->and(Redirect::query()->where('site', 'default')->get())->toHaveCount(1);
    });

    it('creates redirects for child pages when their parent moves', function () {
        [$home, $about, $team, $bio] = structuredPages(withBio: true);
        $services = tap(Entry::make()->collection('pages')->locale('default')->slug('services'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $services->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id(), 'children' => [
                    ['entry' => $bio->id()],
                ]],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $services->id(), 'children' => [
                ['entry' => $about->id(), 'children' => [
                    ['entry' => $team->id(), 'children' => [
                        ['entry' => $bio->id()],
                    ]],
                ]],
            ]],
        ]);

        $redirects = redirectMap();

        expect($redirects)->toHaveCount(3)
            ->and($redirects->get('/about'))->toBe("entry::{$about->id()}")
            ->and($redirects->get('/about/team'))->toBe("entry::{$team->id()}")
            ->and($redirects->get('/about/team/bio'))->toBe("entry::{$bio->id()}");
    });

    it('creates redirects for child pages when a parent slug changes', function () {
        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        $about->slug('company')->save();

        $redirects = redirectMap();

        expect($redirects->get('/about'))->toBe("entry::{$about->id()}")
            ->and($redirects->get('/about/team'))->toBe("entry::{$team->id()}")
            ->and(Stache::store('entries')->store('pages')->index('uri')->get($team->id()))->toBe('/company/team');
    });

    it('creates redirects for child pages when a parent date changes the url', function () {
        Collection::find('pages')->dated(true)->routes('/{year}/{parent_uri}/{slug}')->save();

        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home')->date('2024-01-01'))->save();
        $about = tap(Entry::make()->collection('pages')->locale('default')->slug('about')->date('2024-01-15'))->save();
        $team = tap(Entry::make()->collection('pages')->locale('default')->slug('team')->date('2024-06-01'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        $about->date('2025-06-20')->save();

        $redirects = redirectMap();

        expect($redirects->get('/2024/about'))->toBe("entry::{$about->id()}")
            ->and($redirects->get('/2024/2024/about/team'))->toBe("entry::{$team->id()}")
            ->and(Stache::store('entries')->store('pages')->index('uri')->get($team->id()))->toBe('/2024/2025/about/team');
    });

    it('creates redirects for child pages when a parent route token changes the url', function () {
        Collection::find('pages')->routes('/{parent_uri}/{category}/{slug}')->save();

        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home')->set('category', 'home'))->save();
        $about = tap(Entry::make()->collection('pages')->locale('default')->slug('about')->set('category', 'news'))->save();
        $team = tap(Entry::make()->collection('pages')->locale('default')->slug('team')->set('category', 'people'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        $about->set('category', 'updates')->save();

        $redirects = redirectMap();

        expect($redirects->get('/news/about'))->toBe("entry::{$about->id()}")
            ->and($redirects->get('/news/about/people/team'))->toBe("entry::{$team->id()}")
            ->and(Stache::store('entries')->store('pages')->index('uri')->get($team->id()))->toBe('/updates/about/people/team');
    });

    it('creates redirects for grandchildren when a parent slug changes', function () {
        [$home, $about, $team, $bio] = structuredPages(withBio: true);

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id(), 'children' => [
                    ['entry' => $bio->id()],
                ]],
            ]],
        ]);

        forgetRedirects();

        $about->slug('company')->save();

        $redirects = redirectMap();

        expect($redirects->get('/about/team'))->toBe("entry::{$team->id()}")
            ->and($redirects->get('/about/team/bio'))->toBe("entry::{$bio->id()}");
    });

    it('redirects published child pages when an unpublished parent slug changes', function () {
        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        $about->published(false)->save();
        forgetRedirects();

        $about->slug('company')->save();

        $redirects = redirectMap();

        expect($redirects->get('/about'))->toBeNull()
            ->and($redirects->get('/about/team'))->toBe("entry::{$team->id()}");
    });

    it('skips a draft child page when a parent slug changes', function () {
        [$home, $about] = structuredPages();
        $draft = tap(Entry::make()->collection('pages')->locale('default')->slug('draft')->published(false))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $draft->id()],
            ]],
        ]);

        forgetRedirects();

        $about->slug('company')->save();

        $redirects = redirectMap();

        expect($redirects->get('/about'))->toBe("entry::{$about->id()}")
            ->and($redirects->has('/about/draft'))->toBeFalse();
    });

    it('creates nothing for child pages when the collection toggle is off and a parent slug changes', function () {
        Seo::find('collections::pages')->config()->set('redirects', false)->save();

        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        $about->slug('company')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing for child pages on the free edition when a parent slug changes', function () {
        useFreeEdition();

        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        $about->slug('company')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when siblings are reordered', function () {
        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $team->id()],
            ['entry' => $about->id()],
        ]);

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('deletes an automatic redirect that would shadow the entry after moving it back', function () {
        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        $sources = Redirect::query()->where('site', 'default')->get()->map->source();

        expect($sources->all())->toBe(['/team']);
    });

    it('leaves a manual redirect for the old url untouched when an entry moves', function () {
        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        Redirect::make()->source('/about/team')->destination('/custom')->site('default')->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/about/team')->first();

        expect($redirect->destination())->toBe('/custom')
            ->and(Redirect::query()->where('site', 'default')->get())->toHaveCount(1);
    });

    it('creates nothing when a scheduled entry moves', function () {
        Collection::find('pages')->dated(true)->futureDateBehavior('private')->save();

        [$home, $about] = structuredPages();
        $scheduled = tap(Entry::make()->collection('pages')->locale('default')->slug('later')->date('2999-01-01'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $scheduled->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $scheduled->id()],
        ]);

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('redirects from the physical url when the entry has a redirect field', function () {
        [$home, $about, $team] = structuredPages();

        $team->set('redirect', '/elsewhere')->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/about/team')->first();
        $team = Entry::find($team->id());

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$team->id()}")
            ->and($redirect->destinationUrl())->toBe($team->absoluteUrl())
            ->and($team->absoluteUrlWithoutRedirect())->not->toBe($team->absoluteUrl());
    });

    it('creates nothing when a draft entry moves', function () {
        [$home, $about] = structuredPages();
        $draft = tap(Entry::make()->collection('pages')->locale('default')->slug('draft')->published(false))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $draft->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $draft->id()],
        ]);

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when the collection toggle is off', function () {
        Seo::find('collections::pages')->config()->set('redirects', false)->save();

        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing on the free edition', function () {
        useFreeEdition();

        [$home, $about, $team] = structuredPages();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates a prefix-stripped redirect in the moved entry site', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::find('pages')->sites(['en', 'fr'])->save();

        $home = tap(Entry::make()->collection('pages')->locale('fr')->slug('home'))->save();
        $about = tap(Entry::make()->collection('pages')->locale('fr')->slug('a-propos'))->save();
        $team = tap(Entry::make()->collection('pages')->locale('fr')->slug('equipe'))->save();

        Collection::find('pages')->structure()->in('fr')->tree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ])->save();

        Redirect::query()->get()->each->delete();

        Collection::find('pages')->structure()->in('fr')->tree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ])->save();

        $redirect = Redirect::query()->where('site', 'fr')->where('source', '/a-propos/equipe')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$team->id()}")
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
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

        Redirect::query()->get()->each->delete();

        Term::find('topics::old')->in('en')->slug('new')->save();

        expect(Redirect::query()->where('site', 'en')->where('source', '/topics/old')->first())->not->toBeNull()
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
    });

    it('creates a redirect when a localization slug is overridden', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();

        Term::find('topics::old')->in('fr')->slug('vieux')->save();

        $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/old')->first();

        expect($fr)->not->toBeNull()
            ->and($fr->destination())->toBe('/topics/vieux')
            ->and($fr->origin())->toBe(RedirectOrigin::Automatic)
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
    });

    it('creates a redirect when an overridden localization slug changes', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();
        Term::find('topics::old')->in('fr')->data(['title' => 'Vieux'])->slug('vieux')->save();

        Redirect::query()->get()->each->delete();

        Term::find('topics::old')->in('fr')->slug('ancien')->save();

        $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/vieux')->first();

        expect($fr)->not->toBeNull()
            ->and($fr->destination())->toBe('/topics/ancien')
            ->and($fr->origin())->toBe(RedirectOrigin::Automatic)
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
    });

    it('creates a redirect when an overridden localization slug is cleared', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();
        Term::find('topics::old')->in('fr')->data(['title' => 'Vieux'])->slug('vieux')->save();

        Redirect::query()->get()->each->delete();

        Term::find('topics::old')->in('fr')->set('slug', null)->save();

        $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/vieux')->first();

        expect($fr)->not->toBeNull()
            ->and($fr->destination())->toBe('/topics/old')
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
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

function structuredPages(bool $withBio = false): array
{
    $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home'))->save();
    $about = tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();
    $team = tap(Entry::make()->collection('pages')->locale('default')->slug('team'))->save();

    $pages = [$home, $about, $team];

    if ($withBio) {
        $pages[] = tap(Entry::make()->collection('pages')->locale('default')->slug('bio'))->save();
    }

    return $pages;
}

function saveTree(array $tree): void
{
    Collection::find('pages')->structure()->in('default')->tree($tree)->save();
}

function forgetRedirects(): void
{
    Redirect::query()->get()->each->delete();
}

function redirectMap()
{
    return Redirect::query()->where('site', 'default')->get()
        ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);
}
