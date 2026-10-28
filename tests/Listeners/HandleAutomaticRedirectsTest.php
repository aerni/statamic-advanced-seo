<?php

use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Redirects\AutomaticRedirectWriter;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Statamic\Events\CollectionTreeEntriesMovedOrRemoved;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Taxonomies\LocalizedTerm;
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

    it('creates a redirect when a slugless entry route field changes', function () {
        Collection::make('notes')->requiresSlugs(false)->routes('/{section}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('notes')->locale('default')->set('section', 'news'))->save();

        $entry->set('section', 'updates')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/news')->first();

        expect($entry->slug())->toBeNull()
            ->and($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}")
            ->and(Redirect::query()->where('source', '/'.$entry->id())->get())->toHaveCount(0);
    });

    it('creates a redirect from the id when a slugless entry route uses the id', function () {
        Collection::make('notes')->requiresSlugs(false)->routes('/{id}/{section}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('notes')->locale('default')->set('section', 'news'))->save();

        $entry->set('section', 'updates')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', "/{$entry->id()}/news")->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates nothing when the collection has no route', function () {
        Collection::make('notes')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('notes')->locale('default')->slug('old'))->save();

        $entry->slug('new')->save();

        expect(Redirect::query()->get())->toHaveCount(0);
    });

    it('creates a redirect only for the localization whose site has a route', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes([
            'fr' => '/{category}/{slug}',
        ])->sites(['en', 'fr'])->saveQuietly();
        Blink::flush();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $en->set('category', 'updates')->save();

        $localization = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();

        expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
            ->and($localization)->not->toBeNull()
            ->and($localization->destination())->toBe("entry::{$fr->id()}");
    });

    it('creates nothing for a localization whose site has no route', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes([
            'en' => '/{category}/{slug}',
        ])->sites(['en', 'fr'])->saveQuietly();
        Blink::flush();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $en->set('category', 'updates')->save();

        $origin = Redirect::query()->where('site', 'en')->where('source', '/news/post')->first();

        expect($origin?->destination())->toBe("entry::{$en->id()}")
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
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

    it('creates a redirect when the control panel moves a date across the utc day', function () {
        $timezone = config('app.timezone');

        try {
            config(['app.timezone' => 'Europe/Zurich']);

            Collection::make('journal')->dated(true)->routes('/blog/{year}/{month}/{day}/{slug}')->sites(['default'])->saveQuietly();

            Blueprint::make('collections.journal::journal')->setContents([
                'tabs' => [
                    'main' => [
                        'sections' => [[
                            'fields' => [[
                                'handle' => 'date',
                                'field' => [
                                    'type' => 'date',
                                    'required' => true,
                                ],
                            ]],
                        ]],
                    ],
                ],
            ])->save();

            Blink::flush();

            $entry = tap(Entry::make()->collection('journal')->locale('default')->slug('post')->date('2024-01-15'))->save();

            // The control panel augments the date field, which returns a UTC Carbon.
            $entry->date($entry->blueprint()->field('date')->fieldtype()->augment('2024-01-16'))->save();

            $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/2024/01/15/post')->first();

            expect($redirect)->not->toBeNull()
                ->and($redirect->destination())->toBe("entry::{$entry->id()}");
        } finally {
            config(['app.timezone' => $timezone]);
        }
    });

    it('redirects control panel date changes independently of display timezones', function (string $appTimezone, string $cpTimezone, ?string $displayTimezone, string $input, string $oldPath, string $newPath) {
        $originalTimezone = date_default_timezone_get();

        try {
            config([
                'app.timezone' => $appTimezone,
                'statamic.cp.default_timezone' => $cpTimezone,
                'statamic.system.display_timezone' => $displayTimezone,
            ]);
            date_default_timezone_set($appTimezone);
            $this->travelTo(Carbon::parse('2024-02-01 12:00:00', $appTimezone));

            Collection::make('journal')->dated(true)->routes('/{{ date format="Y-m-d-H-i" }}/{{ slug }}')->sites(['default'])->saveQuietly();
            Blueprint::make('collections.journal::journal')->setContents([
                'tabs' => [
                    'main' => [
                        'sections' => [[
                            'fields' => [[
                                'handle' => 'date',
                                'field' => [
                                    'type' => 'date',
                                    'time_enabled' => true,
                                ],
                            ]],
                        ]],
                    ],
                ],
            ])->save();
            Blink::flush();

            $entry = Entry::make()->collection('journal')->locale('default')->slug('post');
            $fieldtype = $entry->blueprint()->field('date')->fieldtype();
            $entry->date($fieldtype->augment($fieldtype->process($input)))->save();
            $newDate = Carbon::parse($input)->addHour()->toIso8601String();

            $entry->date($fieldtype->augment($fieldtype->process($newDate)))->save();

            expect(Redirect::resolve($oldPath, 'default')?->destination)->toBe('http://localhost'.$newPath);
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    })->with([
        'UTC storage, Zurich CP' => ['UTC', 'Europe/Zurich', null, '2024-01-14T23:30:00Z', '/2024-01-14-23-30/post', '/2024-01-15-00-30/post'],
        'Zurich storage, Zurich CP' => ['Europe/Zurich', 'Europe/Zurich', null, '2024-01-14T23:30:00Z', '/2024-01-15-00-30/post', '/2024-01-15-01-30/post'],
        'separate frontend timezone' => ['UTC', 'Europe/Zurich', 'America/New_York', '2024-01-14T23:30:00Z', '/2024-01-14-18-30/post', '/2024-01-14-19-30/post'],
        'three different timezones' => ['Europe/Zurich', 'America/New_York', 'UTC', '2024-01-15T05:30:00Z', '/2024-01-15-05-30/post', '/2024-01-15-06-30/post'],
    ]);

    it('creates a redirect when a dated entry without a filename date gets an explicit date', function () {
        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post'))->save();

        $year = $entry->date()->format('Y');

        expect(basename($entry->initialPath()))->toBe('post.md');

        $entry->date('2019-03-04')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', "/blog/{$year}/post")->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates a redirect from the fallback date when a control panel save changes the date', function () {
        $this->travelTo('2026-06-20 12:00:00');
        config(['statamic.system.track_last_update' => true]);

        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('updated_at', strtotime('2024-01-15 12:00:00')))->save();

        expect(basename($entry->initialPath()))->toBe('post.md')
            ->and($entry->uri())->toBe('/blog/2024/post');

        Blink::flush();
        $entry = Entry::find($entry->id());
        $entry->date('2025-03-04')->set('title', 'Updated')->updateLastModified()->save();
        Blink::flush();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/2024/post')->first();

        expect(Entry::findByUri('/blog/2025/post', 'default')?->id())->toBe($entry->id())
            ->and(Redirect::query()->get())->toHaveCount(1)
            ->and($redirect?->destination())->toBe("entry::{$entry->id()}")
            ->and(Redirect::resolve('/blog/2024/post', 'default')?->destination)->toBe($entry->absoluteUrl());
    });

    it('creates nothing when a control panel save keeps the fallback date', function () {
        $this->travelTo('2026-06-20 12:00:00');
        config(['statamic.system.track_last_update' => true]);

        Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('updated_at', strtotime('2024-01-15 12:00:00')))->save();

        expect(basename($entry->initialPath()))->toBe('post.md')
            ->and($entry->uri())->toBe('/blog/2024/post');

        Blink::flush();
        $entry = Entry::find($entry->id());
        $entry->date('2024-01-15')->set('title', 'Updated')->updateLastModified()->save();
        Blink::flush();

        expect(Entry::findByUri('/blog/2024/post', 'default')?->id())->toBe($entry->id())
            ->and(Redirect::query()->get())->toHaveCount(0);
    });

    it('creates a redirect when a route token field changes the url', function () {
        Collection::find('pages')->routes('/{category}/{slug}')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('category', 'news'))->save();

        $entry->set('category', 'updates')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates a redirect when a stored date-related route field changes in a non-dated collection', function (string $field, string $oldValue, string $newValue) {
        Collection::make('archive')->routes('/{'.$field.'}/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('archive')->locale('default')->slug('post')->set($field, $oldValue))->save();

        expect($entry->uri())->toBe("/{$oldValue}/post");

        Blink::flush();
        $entry = Entry::find($entry->id());

        expect($entry->get($field))->toBe($oldValue);

        $entry->set($field, $newValue)->save();
        Blink::flush();

        $redirect = Redirect::query()->where('site', 'default')->where('source', "/{$oldValue}/post")->first();

        expect(Entry::findByUri("/{$newValue}/post", 'default')?->id())->toBe($entry->id())
            ->and(Redirect::query()->get())->toHaveCount(1)
            ->and($redirect?->destination())->toBe("entry::{$entry->id()}")
            ->and(Redirect::resolve("/{$oldValue}/post", 'default')?->destination)->toBe($entry->absoluteUrl());
    })->with([
        'year' => ['year', '2024', '2025'],
        'month' => ['month', '01', '06'],
        'day' => ['day', '15', '20'],
        'date' => ['date', '2024-01-15', '2025-06-20'],
    ]);

    it('creates redirects from each previous route field value on consecutive saves', function () {
        Collection::make('pages')->routes('/{category}/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('category', 'news'))->save();

        $entry->set('category', 'updates')->set('title', 'Updated')->save();
        $entry->set('category', 'archive')->set('title', 'Archived')->save();

        $first = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();
        $second = Redirect::query()->where('site', 'default')->where('source', '/updates/post')->first();
        $saved = Entry::find($entry->id());

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(2)
            ->and($first?->destination())->toBe("entry::{$entry->id()}")
            ->and($second?->destination())->toBe("entry::{$entry->id()}")
            ->and($saved->get('category'))->toBe('archive')
            ->and($saved->get('title'))->toBe('Archived')
            ->and($saved->uri())->toBe('/archive/post');
    });

    it('creates a redirect when a route token of zero is cleared', function () {
        Collection::find('pages')->routes('/{floor}/{slug}')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('floor', 0))->save();

        $entry->set('floor', null)->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/0/post')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}");
    });

    it('creates nothing when a route token of zero is saved again as a string', function () {
        Collection::find('pages')->routes('/{floor}/{slug}')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('floor', 0))->save();

        $entry->set('floor', '0')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates a redirect when the first value of an array route field changes', function () {
        Collection::find('pages')->routes('/{{ categories | first }}/{{ slug }}')->save();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->set('categories', ['news']))->save();

        $entry->set('categories', ['updates'])->save();

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

    it('creates nothing when a public term still owns the old url', function (string $route, string $template) {
        Collection::find('pages')->routes($route)->saveQuietly();
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();
        Collection::make('blog')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('shared')->data(['title' => 'Shared']))->save();
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('shared'))->save();

        withTermViews([$template], function () use ($entry) {
            $entry->slug('moved')->save();

            expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
        });
    })->with([
        'canonical term' => ['/tags/{slug}', 'tags.show'],
        'collection-scoped term' => ['/blog/tags/{slug}', 'blog.tags.show'],
    ]);

    it('creates a redirect when a matching term is not publicly served', function (string $route, array $templates) {
        Collection::find('pages')->routes($route)->saveQuietly();
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();
        Collection::make('blog')->sites(['default'])->saveQuietly();
        Blink::flush();

        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('shared')->data(['title' => 'Shared']))->save();
        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('shared'))->save();
        $oldPath = $entry->uri();

        withTermViews($templates, function () use ($entry, $oldPath) {
            $entry->slug('moved')->save();

            expect(Redirect::query()->where('site', 'default')->where('source', $oldPath)->first()?->destination())->toBe("entry::{$entry->id()}");
        });
    })->with([
        'missing term template' => ['/tags/{slug}', []],
        'unrelated collection with a template' => ['/blog/tags/{slug}', ['blog.tags.show']],
    ]);

    it('creates a redirect when a matching term is unpublished', function () {
        $term = Mockery::mock(LocalizedTerm::class);
        $term->shouldReceive('status')->andReturn('draft');
        $term->shouldReceive('template')->andReturn('tags.show');
        $term->shouldReceive('collection')->andReturnNull();

        Term::partialMock()->shouldReceive('findByUri')
            ->with('/tags/shared', 'default')
            ->andReturn($term);

        withTermViews(['tags.show'], function () {
            app(AutomaticRedirectWriter::class)->create('/tags/shared', '/moved', 'default');

            expect(Redirect::query()->where('site', 'default')->where('source', '/tags/shared')->first()?->destination())->toBe('/moved');
        });
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

    it('creates a redirect when an automatic title changes the url', function () {
        Collection::make('pages')->routes('/{title}')->titleFormats(['default' => '{{ name }}'])->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('post')->data([
            'title' => 'Hello',
            'name' => 'Hello',
        ]))->save();

        forgetRedirects();

        $entry->set('name', 'World')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/hello')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$entry->id()}")
            ->and(Entry::find($entry->id())->get('title'))->toBe('World');
    });

    it('creates nothing when an expired entry is published and its slug changes in the same save', function () {
        $this->travelTo('2024-01-01 12:00:00');

        Collection::make('posts')->dated(true)->pastDateBehavior('private')->routes('/blog/{slug}')->sites(['default'])->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('old')->date('2020-01-01'))->save();

        expect($entry->status())->toBe('expired');

        $entry->slug('new')->date('2024-06-20')->save();

        expect($entry->status())->toBe('published')
            ->and(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
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

    it('keeps a cleared localization route field absent', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article')->set('category', 'actualites'))->save();

        forgetRedirects();

        $fr->remove('category')->save();

        $redirect = Redirect::query()->where('site', 'fr')->where('source', '/actualites/article')->first();

        expect(Entry::find($fr->id())->has('category'))->toBeFalse()
            ->and($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$fr->id()}");
    });

    it('creates a redirect when a localization stops storing null and inherits', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article')->set('category', null))->save();

        forgetRedirects();

        $fr->remove('category')->save();

        $redirect = Redirect::query()->where('site', 'fr')->where('source', '/article')->first();

        expect(Entry::find($fr->id())->has('category'))->toBeFalse()
            ->and($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$fr->id()}");
    });

    it('creates a redirect when a localization stores null over an inherited route field', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $fr->set('category', null)->save();

        $redirect = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();
        $saved = Entry::find($fr->id());

        expect($saved->has('category'))->toBeTrue()
            ->and($saved->get('category'))->toBeNull()
            ->and($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$fr->id()}");
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

    it('creates redirects when inherited localization date and route fields change together', function () {
        $this->travelTo('2026-01-01 12:00:00');

        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->dated(true)->routes('/{year}/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();
        Blueprint::make('collections.articles::article')->setContents([
            'tabs' => [
                'main' => [
                    'sections' => [[
                        'fields' => [
                            ['handle' => 'category', 'field' => ['type' => 'text', 'localizable' => true]],
                            ['handle' => 'date', 'field' => ['type' => 'date', 'localizable' => true]],
                        ],
                    ]],
                ],
            ],
        ])->save();
        Blink::flush();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2024-01-15')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        expect($fr->uri())->toBe('/2024/news/article');

        forgetRedirects();
        Blink::flush();

        $en = Entry::find($en->id());
        $en->date('2025-06-20')->set('category', 'updates')->save();

        Blink::flush();

        expect(Entry::findByUri('/2025/updates/article', 'fr')?->id())->toBe($fr->id());

        $redirects = Redirect::query()->get()->mapWithKeys(fn ($redirect) => [$redirect->site().$redirect->source() => $redirect->destination()]);

        expect($redirects)->toHaveCount(2)
            ->and($redirects->get('en/2024/news/post'))->toBe("entry::{$en->id()}")
            ->and($redirects->get('fr/2024/news/article'))->toBe("entry::{$fr->id()}")
            ->and(Redirect::resolve('/2024/news/article', 'fr')?->destination)->toBe('https://example.com/fr/2025/updates/article');
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

    it('creates a redirect when a localization replaces its inherited date', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->dated(true)->routes('/{year}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2024-01-15'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $fr->date('2025-06-20')->save();

        $redirect = Redirect::query()->where('site', 'fr')->where('source', '/2024/article')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe("entry::{$fr->id()}")
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
    });

    it('creates nothing for a localization when the inherited date was never public', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Collection::make('articles')->dated(true)->futureDateBehavior('private')->routes('/{year}/{slug}')->sites(['en', 'fr'])->saveQuietly();

        $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2999-01-01'))->save();
        tap($en->makeLocalization('fr')->slug('article'))->save();

        forgetRedirects();

        $en->date('2024-06-20')->save();

        expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
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

    it('creates redirects for both trees when one save happens during the other', function () {
        [$home, $about, $team] = structuredPages();

        Collection::make('notes')
            ->routes('/{parent_uri}/{slug}')
            ->sites(['default'])
            ->structureContents(['root' => false])
            ->saveQuietly();

        Blink::flush();

        $alpha = tap(Entry::make()->collection('notes')->locale('default')->slug('alpha'))->save();
        $beta = tap(Entry::make()->collection('notes')->locale('default')->slug('beta'))->save();

        Collection::find('notes')->structure()->in('default')->tree([
            ['entry' => $alpha->id(), 'children' => [
                ['entry' => $beta->id()],
            ]],
        ])->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id()],
            ]],
        ]);

        forgetRedirects();

        $nested = false;

        Event::listen(CollectionTreeEntriesMovedOrRemoved::class, function () use (&$nested, $alpha, $beta) {
            if ($nested) {
                return;
            }

            $nested = true;

            Collection::find('notes')->structure()->in('default')->tree([
                ['entry' => $alpha->id()],
                ['entry' => $beta->id()],
            ])->save();
        });

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $team->id()],
        ]);

        $redirects = redirectMap();

        expect($redirects->get('/about/team'))->toBe("entry::{$team->id()}")
            ->and($redirects->get('/alpha/beta'))->toBe("entry::{$beta->id()}");
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

describe('mount redirects', function () {
    beforeEach(function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    });

    it('redirects mounted urls wherever the mount sits in the route', function (string $route, string $old, string $new) {
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')
            ->dated(true)
            ->routes($route)
            ->mount($blog->id())
            ->sites(['default'])
            ->saveQuietly();

        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello')->date('2024-01-15'))->save();

        expect($post->uri())->toBe($old);

        forgetRedirects();

        $blog->slug('journal')->save();

        Blink::flush();

        $post = Entry::find($post->id());

        expect($post->uri())->toBe($new)
            ->and(redirectMap()->get($old))->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve($old, 'default')->destination)->toBe($post->absoluteUrl());
    })->with([
        'at the start' => ['/{mount}/{slug}', '/blog/hello', '/journal/hello'],
        'in the middle' => ['/{date}/{mount}/{slug}', '/2024-01-15-000000/blog/hello', '/2024-01-15-000000/journal/hello'],
        'at the end' => ['/{slug}/{mount}', '/hello/blog', '/hello/journal'],
        'after a dated path' => ['/{year}/{month}/{day}/{mount}/{slug}', '/2024/01/15/blog/hello', '/2024/01/15/journal/hello'],
    ]);

    it('creates one redirect per mounted entry', function () {
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $hello = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();
        $world = tap(Entry::make()->collection('posts')->locale('default')->slug('world'))->save();

        forgetRedirects();

        $blog->slug('journal')->save();

        $redirects = redirectMap();

        expect($redirects->get('/blog/hello'))->toBe("entry::{$hello->id()}")
            ->and($redirects->get('/blog/world'))->toBe("entry::{$world->id()}");
    });

    it('redirects published entries mounted on a draft page', function () {
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog')->published(false))->save();
        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();
        tap(Entry::make()->collection('posts')->locale('default')->slug('draft')->published(false))->save();

        expect($post->status())->toBe('published')
            ->and(Entry::findByUri('/blog/hello', 'default')?->id())->toBe($post->id());

        forgetRedirects();

        $blog->slug('journal')->save();

        Blink::flush();

        $redirects = redirectMap();

        expect(Entry::findByUri('/journal/hello', 'default')?->id())->toBe($post->id())
            ->and($redirects->get('/blog/hello'))->toBe("entry::{$post->id()}")
            ->and($redirects->has('/blog'))->toBeFalse()
            ->and($redirects->has('/blog/draft'))->toBeFalse()
            ->and(Redirect::resolve('/blog/hello', 'default')?->destination)->toBe($post->absoluteUrl());
    });

    it('redirects published content when a draft mount inherits an origin route field change', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('tags')->sites(['en', 'fr'])->saveQuietly();
        Collection::make('sections')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();
        Blink::flush();

        $en = tap(Entry::make()->collection('sections')->locale('en')->slug('blog')->set('category', 'news'))->save();
        $fr = tap($en->makeLocalization('fr')->slug('journal')->published(false))->save();
        Collection::make('posts')->routes('/{mount}/{slug}')->mount($en->id())->taxonomies(['tags'])->sites(['en', 'fr'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('fr')->slug('bonjour'))->save();
        tap(Entry::make()->collection('posts')->locale('fr')->slug('draft')->published(false))->save();

        expect($fr->status())->toBe('draft')
            ->and(Entry::find($fr->id())->has('category'))->toBeFalse()
            ->and(Entry::findByUri('/news/journal/bonjour', 'fr')?->id())->toBe($post->id());

        withTermViews(['posts.tags.show'], function () use ($en, $fr, $post) {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('design')->data(['title' => 'Design']))->save();

            forgetRedirects();

            $en->set('category', 'updates')->save();

            Blink::flush();

            $redirects = Redirect::query()->where('site', 'fr')->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($redirects->get('/news/journal/bonjour'))->toBe("entry::{$post->id()}")
                ->and(Entry::findByUri('/updates/journal/bonjour', 'fr')?->id())->toBe($post->id())
                ->and(Redirect::resolve('/news/journal/bonjour', 'fr')?->destination)->toBe('https://example.com/fr/updates/journal/bonjour')
                ->and($redirects->get('/news/journal/tags/design'))->toBe('/updates/journal/tags/design')
                ->and($redirects->has('/news/journal'))->toBeFalse()
                ->and($redirects->has('/news/journal/draft'))->toBeFalse()
                ->and(Entry::find($fr->id())->status())->toBe('draft');
        });
    });

    it('redirects each mounted term to its new path', function (bool $mountPublished) {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();

        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog')->published($mountPublished))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () use ($blog, $mountPublished) {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('news')->data(['title' => 'News']))->save();

            forgetRedirects();

            $blog->slug('journal')->save();

            expect(redirectMap()->get('/blog/tags/news'))->toBe('/journal/tags/news')
                ->and(redirectMap()->has('/tags/news'))->toBeFalse()
                ->and(redirectMap()->has('/blog'))->toBe($mountPublished);
        });
    })->with([
        'published mount' => [true],
        'draft mount' => [false],
    ]);

    it('follows a later entry slug change after the mount redirect', function () {
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        $blog->slug('journal')->save();
        $post->slug('hi')->save();

        $post = Entry::find($post->id());

        expect(Redirect::resolve('/blog/hello', 'default')->destination)->toBe($post->absoluteUrl())
            ->and(Redirect::resolve('/journal/hello', 'default')->destination)->toBe($post->absoluteUrl());
    });

    it('points an earlier mount redirect straight at the newest uri', function () {
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        $blog->slug('journal')->save();
        $blog->slug('news')->save();

        $post = Entry::find($post->id());

        expect(redirectMap()->get('/blog/hello'))->toBe("entry::{$post->id()}")
            ->and(redirectMap()->get('/journal/hello'))->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve('/blog/hello', 'default')->destination)->toBe($post->absoluteUrl())
            ->and(Redirect::resolve('/journal/hello', 'default')->destination)->toBe($post->absoluteUrl());
    });

    it('replaces a multi-segment mount uri', function () {
        Collection::find('pages')->routes('/{parent_uri}/{slug}')->structureContents(['root' => true])->save();

        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home'))->save();
        $company = tap(Entry::make()->collection('pages')->locale('default')->slug('company'))->save();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $company->id(), 'children' => [
                ['entry' => $blog->id()],
            ]],
        ]);

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        $blog->slug('journal')->save();

        $post = Entry::find($post->id());

        expect($post->uri())->toBe('/company/journal/hello')
            ->and(redirectMap()->get('/company/blog/hello'))->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve('/company/blog/hello', 'default')->destination)->toBe($post->absoluteUrl());
    });

    it('redirects mounted entries when a parent slug change moves the mount page', function () {
        Collection::find('pages')->routes('/{parent_uri}/{slug}')->structureContents(['root' => true])->save();

        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home'))->save();
        $about = tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $blog->id()],
            ]],
        ]);

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        $about->slug('company')->save();

        $post = Entry::find($post->id());
        $redirects = redirectMap();

        expect($redirects->get('/about'))->toBe("entry::{$about->id()}")
            ->and($redirects->get('/about/blog'))->toBe("entry::{$blog->id()}")
            ->and($redirects->get('/about/blog/hello'))->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve('/about/blog/hello', 'default')->destination)->toBe($post->absoluteUrl());
    });

    it('redirects mounted entries when the mount page moves in the tree', function () {
        Collection::find('pages')->routes('/{parent_uri}/{slug}')->structureContents(['root' => true])->save();

        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home'))->save();
        $about = tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $blog->id()],
        ]);

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $blog->id()],
            ]],
        ]);

        $post = Entry::find($post->id());

        expect(Redirect::query()->where('site', 'default')->where('source', '/blog')->first()?->destination())->toBe("entry::{$blog->id()}")
            ->and(redirectMap()->get('/blog/hello'))->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve('/blog/hello', 'default')->destination)->toBe($post->absoluteUrl());
    });

    it('redirects mounted entries when the mount page collection has redirects disabled', function () {
        Seo::find('collections::pages')->config()->set('redirects', false)->save();

        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        $blog->slug('journal')->save();

        $post = Entry::find($post->id());

        expect(Redirect::query()->where('site', 'default')->where('source', '/blog')->get())->toHaveCount(0)
            ->and(redirectMap()->get('/blog/hello'))->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve('/blog/hello', 'default')->destination)->toBe($post->absoluteUrl());
    });

    it('creates no mounted entry redirect when the mounted collection has redirects disabled', function () {
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();
        Seo::find('collections::posts')->config()->set('redirects', false)->save();

        tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

        forgetRedirects();

        $blog->slug('journal')->save();

        expect(redirectMap()->get('/blog'))->toBe("entry::{$blog->id()}")
            ->and(Redirect::resolve('/blog/hello', 'default'))->toBeNull();
    });

    it('creates nothing on the free edition', function () {
        useFreeEdition();

        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        forgetRedirects();

        $blog->slug('journal')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('redirects entries and terms through nested collection mounts', function (bool $postPublished, bool $postRedirectsEnabled) {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();

        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();
        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Collection::make('articles')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello')->published($postPublished))->save();
        $article = tap(Entry::make()->collection('articles')->locale('default')->slug('story'))->save();
        Collection::make('replies')->routes('/{mount}/{slug}')->mount($post->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Seo::find('collections::posts')->config()->set('redirects', $postRedirectsEnabled)->save();
        Blink::flush();

        $reply = tap(Entry::make()->collection('replies')->locale('default')->slug('one'))->save();

        withTermViews(['replies.tags.show'], function () use ($blog, $post, $article, $reply, $postPublished, $postRedirectsEnabled) {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('news')->data(['title' => 'News']))->save();

            forgetRedirects();

            $blog->slug('journal')->save();

            Blink::flush();

            $redirects = redirectMap();

            expect(Entry::findByUri('/journal/hello', 'default')?->id())->toBe($post->id())
                ->and(Entry::findByUri('/journal/hello/one', 'default')?->id())->toBe($reply->id())
                ->and(Entry::findByUri('/journal/story', 'default')?->id())->toBe($article->id())
                ->and($redirects->get('/blog/hello'))->toBe($postPublished && $postRedirectsEnabled ? "entry::{$post->id()}" : null)
                ->and($redirects->get('/blog/hello/one'))->toBe("entry::{$reply->id()}")
                ->and($redirects->get('/blog/story'))->toBe("entry::{$article->id()}")
                ->and($redirects->get('/blog/hello/tags/news'))->toBe('/journal/hello/tags/news')
                ->and(Redirect::resolve('/blog/hello/one', 'default')?->destination)->toBe($reply->absoluteUrl());
        });
    })->with([
        'published intermediate mount' => [true, true],
        'draft intermediate mount' => [false, true],
        'intermediate collection with redirects disabled' => [true, false],
    ]);

    it('redirects nested mounts when their root moves in the tree', function () {
        Collection::find('pages')->routes('/{parent_uri}/{slug}')->structureContents(['root' => true])->save();

        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home'))->save();
        $about = tap(Entry::make()->collection('pages')->locale('default')->slug('about'))->save();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id()],
            ['entry' => $blog->id()],
        ]);

        Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();
        Collection::make('replies')->routes('/{mount}/{slug}')->mount($post->id())->sites(['default'])->saveQuietly();
        Blink::flush();

        $reply = tap(Entry::make()->collection('replies')->locale('default')->slug('one'))->save();

        forgetRedirects();

        saveTree([
            ['entry' => $home->id()],
            ['entry' => $about->id(), 'children' => [
                ['entry' => $blog->id()],
            ]],
        ]);

        Blink::flush();

        expect(Entry::findByUri('/about/blog/hello/one', 'default')?->id())->toBe($reply->id())
            ->and(redirectMap()->get('/blog/hello/one'))->toBe("entry::{$reply->id()}")
            ->and(Redirect::resolve('/blog/hello/one', 'default')?->destination)->toBe($reply->absoluteUrl());
    });

    it('redirects content when a translated mount resolves to the edited site', function (string $site, string $oldMountPath, string $destination) {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'de' => ['name' => 'DE', 'url' => 'https://example.com/de', 'locale' => 'de'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('tags')->sites(['en', 'de', 'fr'])->saveQuietly();
        Collection::make('sections')->routes('/{slug}')->sites(['en', 'de', 'fr'])->saveQuietly();
        Blink::flush();

        $en = tap(Entry::make()->collection('sections')->locale('en')->slug('blog'))->save();
        $de = tap($en->makeLocalization('de')->slug('journal-de'))->save();
        tap($en->makeLocalization('fr')->slug('journal-fr'))->save();
        Collection::make('posts')->routes('/{mount}/{slug}')->mount($de->id())->taxonomies(['tags'])->sites(['en', 'de', 'fr'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale($site)->slug('hello'))->save();

        expect(Entry::findByUri($oldMountPath.'/hello', $site)?->id())->toBe($post->id());

        withTermViews(['posts.tags.show'], function () use ($en, $post, $site, $oldMountPath, $destination) {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('design')->data(['title' => 'Design']))->save();

            forgetRedirects();

            Entry::find($en->id())->in($site)->slug('updates')->save();

            Blink::flush();

            $redirects = Redirect::query()->where('site', $site)->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($redirects->get($oldMountPath.'/hello'))->toBe("entry::{$post->id()}")
                ->and(Entry::findByUri('/updates/hello', $site)?->id())->toBe($post->id())
                ->and(Redirect::resolve($oldMountPath.'/hello', $site)?->destination)->toBe($destination)
                ->and($redirects->get($oldMountPath.'/tags/design'))->toBe('/updates/tags/design')
                ->and(Collection::find('posts')->uri('de'))->toBe('/journal-de')
                ->and(Redirect::query()->where('site', 'de')->get())->toHaveCount(0);
        });
    })->with([
        'origin site' => ['en', '/blog', 'https://example.com/updates/hello'],
        'sibling translation' => ['fr', '/journal-fr', 'https://example.com/fr/updates/hello'],
    ]);

    it('creates a mounted entry redirect through multiple localization origins', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'de' => ['name' => 'DE', 'url' => 'https://example.com/de', 'locale' => 'de'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);
        Collection::make('sections')->routes('/{slug}')->sites(['en', 'de', 'fr'])->saveQuietly();
        $en = tap(Entry::make()->collection('sections')->locale('en')->slug('blog'))->save();
        $de = tap($en->makeLocalization('de')->slug('journal-de'))->save();
        $fr = tap($de->makeLocalization('fr')->slug('journal-fr'))->save();
        Collection::make('posts')->routes('/{mount}/{slug}')->mount($en->id())->sites(['en', 'de', 'fr'])->saveQuietly();
        Blink::flush();

        $post = tap(Entry::make()->collection('posts')->locale('fr')->slug('hello'))->save();

        expect($post->uri())->toBe('/journal-fr/hello');

        forgetRedirects();

        $fr->slug('actualites')->save();

        Blink::flush();

        expect(Entry::findByUri('/actualites/hello', 'fr')?->id())->toBe($post->id())
            ->and(Redirect::query()->where('site', 'fr')->where('source', '/journal-fr/hello')->first()?->destination())->toBe("entry::{$post->id()}")
            ->and(Redirect::resolve('/journal-fr/hello', 'fr')?->destination)->toBe($post->absoluteUrl());
    });
});

describe('term redirects', function () {
    beforeEach(function () {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();

        // Existing tests expect the canonical term URL. Statamic only serves it
        // when the term view exists, so the default templates are present here.
        foreach (['tags.show', 'topics.show'] as $template) {
            $path = resource_path('views/'.str_replace('.', '/', $template).'.antlers.html');

            File::ensureDirectoryExists(dirname($path));
            File::put($path, '');
        }
    });

    afterEach(function () {
        foreach (['tags.show', 'topics.show'] as $template) {
            File::delete(resource_path('views/'.str_replace('.', '/', $template).'.antlers.html'));
        }
    });

    it('uses site-specific views for canonical and collection term redirects without frontend middleware', function (bool $sharedFallback) {
        Site::setSites([
            'english' => ['url' => 'https://example.com', 'locale' => 'en'],
            'german' => ['url' => 'https://example.com/de', 'locale' => 'de'],
        ]);
        Site::setCurrent('english');
        Taxonomy::make('topics')->sites(['english', 'german'])->termTemplate('redirects.term')->saveQuietly();
        Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german'])->taxonomies(['topics'])->saveQuietly();
        flushBlink();

        $templates = ['english.redirects.term', 'english.blog.redirects.term'];

        if ($sharedFallback) {
            $templates = [...$templates, 'redirects.term', 'blog.redirects.term'];
        }

        withTermViews($templates, function () use ($sharedFallback) {
            Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();
            Term::find('topics::old')->in('german')->data(['title' => 'Alt'])->save();
            forgetRedirects();

            Term::find('topics::old')->in('english')->slug('new')->save();

            $expected = [
                '/topics/old' => '/topics/new',
                '/blog/topics/old' => '/blog/topics/new',
            ];
            $english = Redirect::query()->where('site', 'english')->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()])->all();
            $german = Redirect::query()->where('site', 'german')->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()])->all();

            expect($english)->toBe($expected)
                ->and($german)->toBe($sharedFallback ? $expected : []);
        });
    })->with([
        'site-only views' => false,
        'shared fallbacks' => true,
    ]);

    it('does not borrow another site view when checking term URL ownership', function () {
        Site::setSites([
            'english' => ['url' => 'https://example.com', 'locale' => 'en'],
            'german' => ['url' => 'https://example.de', 'locale' => 'de'],
        ]);
        Taxonomy::make('topics')->sites(['english', 'german'])->termTemplate('redirects.term')->saveQuietly();
        view()->addLocation(resource_path('views/english'));
        Site::setCurrent('german');
        flushBlink();

        withTermViews(['english.redirects.term'], function () {
            Term::make()->taxonomy('topics')->inDefaultLocale()->slug('public')->data(['title' => 'Public'])->save();

            app(AutomaticRedirectWriter::class)->create('/topics/public', '/elsewhere', 'german');

            $redirect = Redirect::query()->where('site', 'german')->where('source', '/topics/public')->first();

            expect($redirect?->destination())->toBe('/elsewhere');
        });
    });

    it('protects a public term URL with a site-only view from automatic redirects', function () {
        Site::setSites([
            'english' => ['url' => 'https://example.com', 'locale' => 'en'],
        ]);
        Taxonomy::make('topics')->sites(['english'])->termTemplate('redirects.term')->saveQuietly();
        flushBlink();

        withTermViews(['english.redirects.term'], function () {
            Term::make()->taxonomy('topics')->inDefaultLocale()->slug('public')->data(['title' => 'Public'])->save();

            app(AutomaticRedirectWriter::class)->create('/topics/public', '/elsewhere', 'english');

            expect(Redirect::query()->where('site', 'english')->get())->toHaveCount(0);
        });
    });

    it('creates nothing when a published entry still owns the old url', function () {
        Collection::make('pages')->routes('/tags/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        tap(Entry::make()->collection('pages')->locale('default')->slug('shared'))->save();
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('shared')->data(['title' => 'Shared']))->save();

        $term->slug('moved')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates nothing when the term view is missing', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data([
            'title' => 'Old',
            'template' => 'tags.missing',
        ]))->save();

        $term->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
    });

    it('creates a redirect only for the localization whose term view exists', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        withTermViews(['topics.fr'], function () {
            Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data([
                'title' => 'Old',
                'template' => 'topics.missing',
            ])->save();

            Term::find('topics::old')->in('fr')->data([
                'title' => 'Vieux',
                'template' => 'topics.fr',
            ])->save();

            Redirect::query()->get()->each->delete();

            Term::find('topics::old')->in('en')->slug('new')->save();

            $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/old')->first();

            expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
                ->and($fr)->not->toBeNull()
                ->and($fr->destination())->toBe('/topics/new');
        });
    });

    it('creates nothing for a localization whose term view is missing', function () {
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);

        Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();

        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();

        Term::find('topics::old')->in('fr')->data([
            'title' => 'Vieux',
            'template' => 'topics.missing',
        ])->save();

        Redirect::query()->get()->each->delete();

        Term::find('topics::old')->in('en')->slug('new')->save();

        $en = Redirect::query()->where('site', 'en')->where('source', '/topics/old')->first();

        expect($en?->destination())->toBe('/topics/new')
            ->and(Redirect::query()->where('site', 'fr')->get())->toHaveCount(0);
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

    it('creates a redirect when a term slug of zero changes', function () {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('0')->data(['title' => 'Zero']))->save();

        $term->slug('new')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/tags/0')->first();

        expect($redirect)->not->toBeNull()
            ->and($redirect->destination())->toBe('/tags/new');
    });

    it('creates a redirect for each collection the term is served under', function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();
        $news = tap(Entry::make()->collection('pages')->locale('default')->slug('news'))->save();

        Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Collection::make('articles')->routes('/articles/{slug}')->mount($news->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show', 'articles.tags.show'], function () {
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

            $term->slug('new')->save();

            $scoped = Term::find('tags::new')->in('default');

            $redirects = Redirect::query()->where('site', 'default')->get()
                ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($scoped->collection(Collection::find('posts'))->uri())->toBe('/blog/tags/new')
                ->and($scoped->collection(Collection::find('articles'))->uri())->toBe('/news/tags/new')
                ->and($redirects)->toHaveCount(3)
                ->and($redirects->get('/tags/old'))->toBe('/tags/new')
                ->and($redirects->get('/blog/tags/old'))->toBe('/blog/tags/new')
                ->and($redirects->get('/news/tags/old'))->toBe('/news/tags/new');
        });
    });

    it('redirects canonical and collection urls when a term loaded through its collection url is renamed', function () {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();
        Collection::make('posts')->routes('/posts/{slug}')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();
            $term = Term::findByUri('/posts/tags/old', 'default');

            expect($term->collection()->handle())->toBe('posts');

            $term->slug('new')->save();

            $redirects = Redirect::query()->where('site', 'default')->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($redirects->get('/tags/old'))->toBe('/tags/new')
                ->and($redirects->get('/posts/tags/old'))->toBe('/posts/tags/new')
                ->and($term->collection()->handle())->toBe('posts');
        });
    });

    it('deletes canonical and collection redirects when a term loaded through its collection url is deleted', function () {
        Taxonomy::make('tags')->sites(['default'])->saveQuietly();
        Collection::make('posts')->routes('/posts/{slug}')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
            Redirect::make()->source('/tags/old')->destination('/tags/target')->site('default')->origin(RedirectOrigin::Automatic)->save();
            Redirect::make()->source('/posts/tags/old')->destination('/posts/tags/target')->site('default')->origin(RedirectOrigin::Automatic)->save();
            $manual = tap(Redirect::make()->source('/manual')->destination('/tags/target')->site('default')->origin(RedirectOrigin::Manual))->save();

            Term::findByUri('/posts/tags/target', 'default')->delete();

            $redirects = Redirect::query()->where('site', 'default')->get();

            expect($redirects)->toHaveCount(1)
                ->and($redirects->first()->id())->toBe($manual->id())
                ->and($redirects->first()->destination())->toBe('/tags/target');
        });
    });

    it('deletes canonical and collection automatic redirects when a term with a missing template is deleted', function () {
        Collection::make('posts')->routes('/posts/{slug}')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('design')->data(['title' => 'Design']))->save();
            $term->slug('branding')->save();

            $redirects = Redirect::query()->where('site', 'default')->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($redirects->get('/tags/design'))->toBe('/tags/branding')
                ->and($redirects->get('/posts/tags/design'))->toBe('/posts/tags/branding');

            $manual = tap(Redirect::make()->source('/manual')->destination('/tags/branding')->site('default')->origin(RedirectOrigin::Manual))->save();
            $term->template('missing')->save();

            $term->delete();

            $remaining = Redirect::query()->where('site', 'default')->get();

            expect($remaining)->toHaveCount(1)
                ->and($remaining->first()->id())->toBe($manual->id())
                ->and($remaining->first()->destination())->toBe('/tags/branding');
        });
    });

    it('redirects the public term url of an unmounted collection', function () {
        Collection::make('blog')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['blog.tags.show'], function () {
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

            $term->slug('new')->save();

            expect(Redirect::query()->where('site', 'default')->where('source', '/blog/tags/old')->first()?->destination())->toBe('/blog/tags/new')
                ->and(Redirect::query()->where('site', 'default')->where('source', '/tags/old')->first())->not->toBeNull();
        });
    });

    it('creates nothing for a mounted collection when the term view is missing', function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();
        Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        expect(Redirect::query()->where('site', 'default')->where('source', '/blog/tags/old')->get())->toHaveCount(0)
            ->and(Redirect::query()->where('site', 'default')->where('source', '/tags/old')->first())->not->toBeNull();
    });

    it('creates a collection redirect when the term uses its own template', function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();
        Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['custom.term'], function () {
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old', 'template' => 'custom.term']))->save();

            $term->slug('new')->save();

            $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/tags/old')->first();

            expect($redirect)->not->toBeNull()
                ->and($redirect->destination())->toBe('/blog/tags/new');
        });
    });

    it('creates one redirect when the collection mount is the site root', function () {
        Collection::make('pages')->routes('/')->sites(['default'])->saveQuietly();
        $home = tap(Entry::make()->collection('pages')->locale('default')->slug('home'))->save();

        Collection::make('posts')->routes('/posts/{slug}')->mount($home->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

            $term->slug('new')->save();

            $redirects = Redirect::query()->where('site', 'default')->get();

            expect($redirects)->toHaveCount(1)
                ->and($redirects->first()->source())->toBe('/tags/old')
                ->and($redirects->first()->destination())->toBe('/tags/new');
        });
    });

    it('skips a collection url still owned by a published entry', function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();

        Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Collection::make('stories')->routes('/blog/tags/{slug}')->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            tap(Entry::make()->collection('stories')->locale('default')->slug('old'))->save();
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

            $term->slug('new')->save();

            $redirects = Redirect::query()->where('site', 'default')->get()
                ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($redirects)->toHaveCount(1)
                ->and($redirects->get('/tags/old'))->toBe('/tags/new')
                ->and($redirects->has('/blog/tags/old'))->toBeFalse();
        });
    });

    it('repoints collection scoped term redirects to the newest url', function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();
        Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('a')->data(['title' => 'A']))->save();

            $term->slug('b')->save();
            $term->slug('c')->save();

            $redirects = Redirect::query()->where('site', 'default')->get()
                ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

            expect($redirects->get('/blog/tags/a'))->toBe('/blog/tags/c')
                ->and($redirects->get('/blog/tags/b'))->toBe('/blog/tags/c');
        });
    });

    it('deletes a collection scoped automatic redirect when the term is deleted', function () {
        Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
        $blog = tap(Entry::make()->collection('pages')->locale('default')->slug('blog'))->save();
        Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
        Blink::flush();

        withTermViews(['posts.tags.show'], function () {
            tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
            Redirect::make()->source('/blog/tags/old')->destination('/blog/tags/target')->site('default')->origin(RedirectOrigin::Automatic)->save();

            Term::find('tags::target')->delete();

            expect(Redirect::query()->where('site', 'default')->where('source', '/blog/tags/old')->get())->toHaveCount(0);
        });
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

function withTermViews(array $templates, callable $callback): void
{
    $paths = collect($templates)->map(function (string $template) {
        $path = resource_path('views/'.str_replace('.', '/', $template).'.antlers.html');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');

        return $path;
    });

    try {
        $callback();
    } finally {
        $paths->each(fn (string $path) => File::delete($path));
    }
}

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
