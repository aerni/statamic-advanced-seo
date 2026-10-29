<?php

use Aerni\AdvancedSeo\Eloquent\Redirect as EloquentRedirect;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesEloquentEntries;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Entries\Entry as EloquentEntry;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Entries\UuidEntryModel;
use Statamic\Eloquent\Taxonomies\Term as EloquentTerm;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesEloquentEntries::class, EnablesRedirects::class, UseEloquentDriver::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')
        ->routes('/{parent_uri}/{slug}')
        ->sites(['default'])
        ->structureContents(['root' => true])
        ->save();
});

it('creates a redirect when an eloquent slugless entry route field changes', function () {
    Collection::make('notes')->requiresSlugs(false)->routes('/{section}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('notes')->locale('default')->set('section', 'news'))->save();

    expect($entry)->toBeInstanceOf(EloquentEntry::class)
        ->and($entry->slug())->toBeNull();

    $entry->set('section', 'updates')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/news')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates a redirect when an eloquent entry slug changes', function () {
    Collection::make('articles')->routes('/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('articles')->locale('default')->slug('old'))->save();

    expect($entry)->toBeInstanceOf(EloquentEntry::class);

    $entry->slug('new')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/old')->first();

    expect($redirect)->toBeInstanceOf(EloquentRedirect::class)
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates no redirect when an eloquent entry had no previous url', function () {
    Collection::make('articles')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('articles')->locale('default')->slug('old'))->save();
    Collection::find('articles')->routes('/articles/{slug}')->saveQuietly();
    Blink::flush();

    $entry->slug('new')->save();

    expect($entry->uri())->toBe('/articles/new')
        ->and(Redirect::query()->get())->toHaveCount(0);
});

it('creates a redirect when an eloquent entry date changes the url', function () {
    Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

    $entry->date('2025-06-20')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/2024/post')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates a redirect when an eloquent entry date changes by seconds', function () {
    Collection::make('posts')
        ->dated(true)
        ->routes('/{{ date format="Y-m-d-H-i-s" }}/{{ slug }}')
        ->sites(['default'])
        ->saveQuietly();

    Blueprint::make('collections.posts::post')->setContents([
        'tabs' => [
            'main' => [
                'sections' => [[
                    'fields' => [[
                        'handle' => 'date',
                        'field' => [
                            'type' => 'date',
                            'time_enabled' => true,
                            'time_seconds_enabled' => true,
                        ],
                    ]],
                ]],
            ],
        ],
    ])->save();

    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15-103045'))->save();

    $entry->date('2024-01-15-103050')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/2024-01-15-10-30-45/post')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

describe('non-UTC entry dates', function () {
    beforeEach(function () {
        $this->originalPhpTimezone = date_default_timezone_get();

        Collection::make('posts')->dated(true)->routes('/{{ date format="Y-m-d-H-i-s" }}/{{ slug }}')->sites(['default'])->saveQuietly();

        Blueprint::make('collections.posts::post')->setContents([
            'tabs' => [
                'main' => [
                    'sections' => [[
                        'fields' => [[
                            'handle' => 'date',
                            'field' => [
                                'type' => 'date',
                                'time_enabled' => true,
                                'time_seconds_enabled' => true,
                            ],
                        ]],
                    ]],
                ],
            ],
        ])->save();

        Blink::flush();
    });

    afterEach(function () {
        date_default_timezone_set($this->originalPhpTimezone);
    });

    it('creates no redirect when a non-UTC date is unchanged', function (string $timezone, bool $carbonDate) {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', $timezone));

        $date = $carbonDate ? Carbon::parse('2024-01-15 00:30:45', $timezone) : '2024-01-15-003045';
        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date($date))->save();

        $entry->set('title', 'Updated')->save();

        expect($entry->uri())->toBe('/2024-01-15-00-30-45/post')
            ->and(Redirect::query()->get())->toHaveCount(0);
    })->with(['Europe/Zurich', 'America/New_York'])->with([
        'Carbon date' => true,
        'string date' => false,
    ]);

    it('redirects the original non-UTC URL when the slug changes', function (string $timezone, bool $carbonDate, string $siteUrl, string $destination) {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', $timezone));
        Site::setSites(['default' => ['name' => 'Default', 'url' => $siteUrl, 'locale' => 'en']]);

        $date = $carbonDate ? Carbon::parse('2024-01-15 00:30:45', $timezone) : '2024-01-15-003045';
        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('old')->date($date))->save();

        $entry->slug('new')->save();

        expect(Redirect::query()->get())->toHaveCount(1)
            ->and(Redirect::resolve('/2024-01-15-00-30-45/old', 'default')?->destination)->toBe($destination);
    })->with(['Europe/Zurich', 'America/New_York'])->with([
        'Carbon date' => true,
        'string date' => false,
    ])->with([
        'root site' => ['/', 'http://localhost/2024-01-15-00-30-45/new'],
        'prefixed site' => ['https://example.com/fr', 'https://example.com/fr/2024-01-15-00-30-45/new'],
    ]);

    it('redirects the original non-UTC URL when the date changes', function (string $timezone, bool $carbonDate) {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', $timezone));

        $date = $carbonDate ? Carbon::parse('2024-01-15 00:30:45', $timezone) : '2024-01-15-003045';
        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date($date))->save();

        $entry->date($carbonDate ? Carbon::parse('2024-01-16 01:15:50', $timezone) : '2024-01-16-011550')->save();

        expect(Redirect::query()->get())->toHaveCount(1)
            ->and(Redirect::resolve('/2024-01-15-00-30-45/post', 'default')?->destination)->toBe('http://localhost/2024-01-16-01-15-50/post');
    })->with(['Europe/Zurich', 'America/New_York'])->with([
        'Carbon date' => true,
        'string date' => false,
    ]);

    it('redirects a reloaded timestamp only when its saved URL changes', function (string $timezone) {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', $timezone));

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date(Carbon::parse('2024-01-15 00:30:45', $timezone)))->save();
        $oldPath = $entry->uri();
        Blink::flush();
        $entry = Entry::find($entry->id());

        $entry->set('title', 'Updated')->save();

        $moved = $oldPath !== $entry->uri();

        expect(Redirect::query()->get())->toHaveCount($moved ? 1 : 0)
            ->and(Redirect::resolve($oldPath, 'default')?->destination)->toBe($moved ? $entry->absoluteUrlWithoutRedirect() : null);
    })->with(['Europe/Zurich', 'America/New_York']);

    it('redirects a reloaded date change when the local clock matches the old UTC snapshot', function () {
        config(['app.timezone' => 'Europe/Zurich']);
        date_default_timezone_set('Europe/Zurich');
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', 'Europe/Zurich'));

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15-003045'))->save();
        Blink::flush();
        $entry = Entry::find($entry->id());

        $entry->date('2024-01-14-233045')->save();

        expect(Redirect::resolve('/2024-01-15-00-30-45/post', 'default')?->destination)->toBe('http://localhost/2024-01-14-23-30-45/post');
    });

    it('redirects a reloaded live entry with private future dates outside UTC', function () {
        config(['app.timezone' => 'America/New_York']);
        date_default_timezone_set('America/New_York');
        $this->travelTo(Carbon::parse('2024-01-15 11:00:00', 'America/New_York'));
        Collection::find('posts')->futureDateBehavior('private')->saveQuietly();

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('old')->date('2024-01-15-103045'))->save();
        Blink::flush();
        $entry = Entry::find($entry->id());

        expect($entry->status())->toBe('published');

        $entry->slug('new')->save();

        expect(Redirect::resolve('/2024-01-15-10-30-45/old', 'default')?->destination)->toBe('http://localhost/2024-01-15-10-30-45/new');
    });

    it('redirects a date change between repeated local times at the end of DST', function () {
        config(['app.timezone' => 'Europe/Zurich', 'statamic.system.display_timezone' => 'UTC']);
        date_default_timezone_set('Europe/Zurich');
        $this->travelTo(Carbon::parse('2024-10-28 12:00:00', 'Europe/Zurich'));

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date(Carbon::parse('2024-10-27T00:30:45Z')))->save();

        expect($entry->uri())->toBe('/2024-10-27-00-30-45/post');

        $entry->date(Carbon::parse('2024-10-27T01:30:45Z'))->save();

        expect($entry->uri())->toBe('/2024-10-27-01-30-45/post')
            ->and(Redirect::resolve('/2024-10-27-00-30-45/post', 'default')?->destination)->toBe('http://localhost/2024-10-27-01-30-45/post');
    });

    it('redirects inherited localized dates between repeated local times at the end of DST', function () {
        config(['app.timezone' => 'Europe/Zurich', 'statamic.system.display_timezone' => 'UTC']);
        date_default_timezone_set('Europe/Zurich');
        $this->travelTo(Carbon::parse('2024-10-28 12:00:00', 'Europe/Zurich'));
        Site::setSites([
            'en' => ['name' => 'EN', 'url' => '/', 'locale' => 'en'],
            'fr' => ['name' => 'FR', 'url' => '/fr', 'locale' => 'fr'],
        ]);
        Collection::find('posts')->sites(['en', 'fr'])->saveQuietly();
        Blink::flush();

        $en = tap(Entry::make()->collection('posts')->locale('en')->slug('post')->date(Carbon::parse('2024-10-27T00:30:45Z')))->save();
        $en->makeLocalization('fr')->slug('article')->save();

        $en->date(Carbon::parse('2024-10-27T01:30:45Z'))->save();

        expect(Redirect::resolve('/2024-10-27-00-30-45/post', 'en')?->destination)->toBe('http://localhost/2024-10-27-01-30-45/post')
            ->and(Redirect::resolve('/2024-10-27-00-30-45/article', 'fr')?->destination)->toBe('http://localhost/fr/2024-10-27-01-30-45/article');
    });

    it('redirects mounted content after reloading a repeated-hour date', function () {
        config(['app.timezone' => 'Europe/Zurich', 'statamic.system.display_timezone' => 'UTC']);
        date_default_timezone_set('Europe/Zurich');
        $this->travelTo(Carbon::parse('2024-10-28 12:00:00', 'Europe/Zurich'));
        $mount = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date(Carbon::parse('2024-10-27T00:30:45Z')))->save();
        Collection::make('articles')->routes('/{mount}/{slug}')->mount($mount->id())->sites(['default'])->saveQuietly();
        Blink::flush();
        Entry::make()->collection('articles')->locale('default')->slug('hello')->save();

        Blink::flush();
        $mount = Entry::find($mount->id());
        $mount->set('title', 'Updated')->save();

        expect($mount->uri())->not->toBe('/2024-10-27-00-30-45/post')
            ->and(Redirect::resolve('/2024-10-27-00-30-45/post/hello', 'default')?->destination)->toBe('http://localhost'.$mount->uri().'/hello');
    });

    it('redirects mounted content when its mount moves between repeated local times', function () {
        config(['app.timezone' => 'Europe/Zurich', 'statamic.system.display_timezone' => 'UTC']);
        date_default_timezone_set('Europe/Zurich');
        $this->travelTo(Carbon::parse('2024-10-28 12:00:00', 'Europe/Zurich'));

        $mount = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date(Carbon::parse('2024-10-27T00:30:45Z')))->save();
        Collection::make('articles')->routes('/{mount}/{slug}')->mount($mount->id())->sites(['default'])->saveQuietly();
        Blink::flush();
        Entry::make()->collection('articles')->locale('default')->slug('hello')->save();

        $mount->date(Carbon::parse('2024-10-27T01:30:45Z'))->save();

        expect(Redirect::resolve('/2024-10-27-00-30-45/post/hello', 'default')?->destination)->toBe('http://localhost/2024-10-27-01-30-45/post/hello');
    });

    it('creates no redirect for an unchanged CP date after reloading', function (string $timezone) {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', $timezone));

        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15-103045'))->save();
        Blink::flush();
        $entry = Entry::find($entry->id());
        $input = $entry->date()->toIso8601String();
        $fieldtype = $entry->blueprint()->field('date')->fieldtype();

        $entry->date($fieldtype->augment($fieldtype->process($input)))->save();

        expect(Redirect::query()->get())->toHaveCount(0);
    })->with(['Europe/Zurich', 'America/New_York']);

    it('redirects a fallback date when an Eloquent entry first gets an explicit date', function () {
        config(['app.timezone' => 'Europe/Zurich']);
        date_default_timezone_set('Europe/Zurich');
        $this->travelTo(Carbon::parse('2024-01-15 10:30:45', 'Europe/Zurich'));
        $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post'))->save();
        $this->travelTo(Carbon::parse('2024-02-01 12:00:00', 'Europe/Zurich'));

        $entry->date('2024-01-16-113050')->save();

        expect(Redirect::resolve('/2024-01-15-10-30-45/post', 'default')?->destination)->toBe('http://localhost/2024-01-16-11-30-50/post');
    });

});

it('creates a redirect when an eloquent entry route field changes the url', function () {
    Collection::make('posts')->routes('/{category}/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('category', 'news'))->save();

    $entry->set('category', 'updates')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates a redirect when an eloquent stored date-related route field changes in a non-dated collection', function (string $model, string $field, string $oldValue, string $newValue) {
    eloquentUseEntryModel($model);

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
    'integer IDs' => [EntryModel::class],
    'UUIDs' => [UuidEntryModel::class],
])->with([
    'year' => ['year', '2024', '2025'],
    'month' => ['month', '01', '06'],
    'day' => ['day', '15', '20'],
]);

it('creates eloquent redirects from each previous route field value on consecutive saves', function () {
    Collection::make('posts')->routes('/{category}/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('category', 'news'))->save();

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

it('creates a redirect when an eloquent route token of zero is cleared', function () {
    Collection::make('posts')->routes('/{floor}/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('floor', 0))->save();

    $entry->set('floor', null)->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/0/post')->first();

    expect($redirect)->toBeInstanceOf(EloquentRedirect::class)
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates a redirect when an eloquent term slug changes under a mounted collection', function () {
    Collection::make('sections')->routes('/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $blog = tap(Entry::make()->collection('sections')->locale('default')->slug('blog'))->save();
    Taxonomy::make('tags')->sites(['default'])->saveQuietly();
    Collection::make('posts')->routes('/posts/{slug}')->mount($blog->id())->taxonomies(['tags'])->sites(['default'])->saveQuietly();
    Blink::flush();

    $path = resource_path('views/posts/tags/show.antlers.html');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '');

    try {
        $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

        $term->slug('new')->save();

        $redirect = Redirect::query()->where('site', 'default')->where('source', '/blog/tags/old')->first();

        expect($redirect)->toBeInstanceOf(EloquentRedirect::class)
            ->and($redirect->destination())->toBe('/blog/tags/new');
    } finally {
        File::delete($path);
    }
});

it('redirects canonical and collection urls when an eloquent term loaded through its collection url is renamed', function () {
    Taxonomy::make('tags')->sites(['default'])->saveQuietly();
    Collection::make('posts')->routes('/posts/{slug}')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
    Blink::flush();

    $paths = [resource_path('views/tags/show.antlers.html'), resource_path('views/posts/tags/show.antlers.html')];

    foreach ($paths as $path) {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');
    }

    try {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();
        $term = Term::findByUri('/posts/tags/old', 'default');

        expect($term->collection()->handle())->toBe('posts');

        $term->slug('new')->save();

        $redirects = Redirect::query()->where('site', 'default')->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

        expect($redirects->get('/tags/old'))->toBe('/tags/new')
            ->and($redirects->get('/posts/tags/old'))->toBe('/posts/tags/new')
            ->and($term->collection()->handle())->toBe('posts');
    } finally {
        foreach ($paths as $path) {
            File::delete($path);
        }
    }
});

it('deletes canonical and collection redirects when an eloquent term loaded through its collection url is deleted', function () {
    Taxonomy::make('tags')->sites(['default'])->saveQuietly();
    Collection::make('posts')->routes('/posts/{slug}')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
    Blink::flush();

    $paths = [resource_path('views/tags/show.antlers.html'), resource_path('views/posts/tags/show.antlers.html')];

    foreach ($paths as $path) {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');
    }

    try {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('target')->data(['title' => 'Target']))->save();
        Redirect::make()->source('/tags/old')->destination('/tags/target')->site('default')->origin(RedirectOrigin::Automatic)->save();
        Redirect::make()->source('/posts/tags/old')->destination('/posts/tags/target')->site('default')->origin(RedirectOrigin::Automatic)->save();
        $manual = tap(Redirect::make()->source('/manual')->destination('/tags/target')->site('default')->origin(RedirectOrigin::Manual))->save();

        Term::findByUri('/posts/tags/target', 'default')->delete();

        $redirects = Redirect::query()->where('site', 'default')->get();

        expect($redirects)->toHaveCount(1)
            ->and($redirects->first()->id())->toBe($manual->id())
            ->and($redirects->first()->destination())->toBe('/tags/target');
    } finally {
        foreach ($paths as $path) {
            File::delete($path);
        }
    }
});

it('deletes canonical and collection automatic redirects when an eloquent term with a missing template is deleted', function () {
    Taxonomy::make('tags')->sites(['default'])->saveQuietly();
    Collection::make('posts')->routes('/posts/{slug}')->taxonomies(['tags'])->sites(['default'])->saveQuietly();
    Blink::flush();

    $paths = [resource_path('views/tags/show.antlers.html'), resource_path('views/posts/tags/show.antlers.html')];

    foreach ($paths as $path) {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');
    }

    try {
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
    } finally {
        foreach ($paths as $path) {
            File::delete($path);
        }
    }
});

it('creates a redirect when an eloquent term localization slug changes', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $path = resource_path('views/topics/show.antlers.html');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '');

    try {
        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();

        expect(Term::find('topics::old'))->toBeInstanceOf(EloquentTerm::class);

        Term::find('topics::old')->in('fr')->slug('vieux')->save();

        eloquentForgetRedirects();

        Term::find('topics::old')->in('fr')->slug('ancien')->save();

        $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/vieux')->first();

        expect($fr)->not->toBeNull()
            ->and($fr->destination())->toBe('/topics/ancien')
            ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
    } finally {
        File::delete($path);
    }
});

it('creates a redirect when an eloquent term localization slug changes and the base slug is zero', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $path = resource_path('views/topics/show.antlers.html');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '');

    try {
        Term::make()->taxonomy('topics')->inDefaultLocale()->slug('0')->data(['title' => 'Zero'])->save();

        Term::find('topics::0')->in('fr')->slug('vieux')->save();

        eloquentForgetRedirects();

        Term::find('topics::0')->in('fr')->slug('ancien')->save();

        $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/vieux')->first();

        expect($fr)->not->toBeNull()
            ->and($fr->destination())->toBe('/topics/ancien');
    } finally {
        File::delete($path);
    }
});

it('creates a redirect when an eloquent entry moves in the tree', function (string $model, string $idType) {
    eloquentUseEntryModel($model);
    [$home, $about, $team] = eloquentStructuredPages();

    expect($team)->toBeInstanceOf(EloquentEntry::class)
        ->and(get_debug_type($team->id()))->toBe($idType);

    eloquentSaveTree([
        ['entry' => $home->id()],
        ['entry' => $about->id(), 'children' => [
            ['entry' => $team->id()],
        ]],
    ]);

    eloquentForgetRedirects();

    eloquentSaveTree([
        ['entry' => $home->id()],
        ['entry' => $about->id()],
        ['entry' => $team->id()],
    ]);

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/about/team')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$team->id()}")
        ->and(Entry::findByUri('/team', 'default')?->id())->toBe($team->id())
        ->and(Redirect::resolve('/about/team', 'default')?->destination)->toBe($team->absoluteUrl());
})->with([
    'integer IDs' => [EntryModel::class, 'int'],
    'UUIDs' => [UuidEntryModel::class, 'string'],
]);

it('creates redirects for eloquent child pages when their parent moves', function () {
    [$home, $about, $team, $bio] = eloquentStructuredPages(withBio: true);
    $services = tap(Entry::make()->collection('pages')->locale('default')->slug('services'))->save();

    eloquentSaveTree([
        ['entry' => $home->id()],
        ['entry' => $services->id()],
        ['entry' => $about->id(), 'children' => [
            ['entry' => $team->id(), 'children' => [
                ['entry' => $bio->id()],
            ]],
        ]],
    ]);

    eloquentForgetRedirects();

    eloquentSaveTree([
        ['entry' => $home->id()],
        ['entry' => $services->id(), 'children' => [
            ['entry' => $about->id(), 'children' => [
                ['entry' => $team->id(), 'children' => [
                    ['entry' => $bio->id()],
                ]],
            ]],
        ]],
    ]);

    $redirects = Redirect::query()->where('site', 'default')->get()
        ->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

    expect($redirects)->toHaveCount(3)
        ->and($redirects->get('/about'))->toBe("entry::{$about->id()}")
        ->and($redirects->get('/about/team'))->toBe("entry::{$team->id()}")
        ->and($redirects->get('/about/team/bio'))->toBe("entry::{$bio->id()}");
});

it('creates a redirect for an eloquent child page when a parent slug changes', function (string $model, string $idType) {
    eloquentUseEntryModel($model);
    [$home, $about, $team] = eloquentStructuredPages();

    expect(get_debug_type($team->id()))->toBe($idType);

    eloquentSaveTree([
        ['entry' => $home->id()],
        ['entry' => $about->id(), 'children' => [
            ['entry' => $team->id()],
        ]],
    ]);

    eloquentForgetRedirects();

    Entry::find($about->id())->slug('company')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/about/team')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$team->id()}")
        ->and(Entry::find($team->id())->model()->fresh()->getAttribute('uri'))->toBe('/company/team')
        ->and(Entry::findByUri('/company/team', 'default')?->id())->toBe($team->id())
        ->and(Redirect::resolve('/about/team', 'default')?->destination)->toBe($team->absoluteUrl());
})->with([
    'integer IDs' => [EntryModel::class, 'int'],
    'UUIDs' => [UuidEntryModel::class, 'string'],
]);

it('creates nothing when an eloquent draft entry is published and its slug changes in the same save', function () {
    Collection::make('articles')->routes('/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('articles')->locale('default')->slug('old')->published(false))->save();

    $entry->slug('new')->published(true)->save();

    expect(Redirect::query()->where('site', 'default')->get())->toHaveCount(0);
});

it('creates a redirect when an eloquent inherited localization route field changes', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
    $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

    expect($en)->toBeInstanceOf(EloquentEntry::class)
        ->and($fr)->toBeInstanceOf(EloquentEntry::class);

    eloquentForgetRedirects();

    $en->set('category', 'updates')->save();

    $origin = Redirect::query()->where('site', 'en')->where('source', '/news/post')->first();
    $localization = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();

    expect($origin?->destination())->toBe("entry::{$en->id()}")
        ->and($localization)->not->toBeNull()
        ->and($localization->destination())->toBe("entry::{$fr->id()}");
});

it('creates a redirect when an eloquent localization stops storing null and inherits', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
    $fr = tap($en->makeLocalization('fr')->slug('article')->set('category', null))->save();

    eloquentForgetRedirects();

    $fr->remove('category')->save();

    $redirect = Redirect::query()->where('site', 'fr')->where('source', '/article')->first();

    expect(Entry::find($fr->id())->has('category'))->toBeFalse()
        ->and($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$fr->id()}");
});

it('creates a redirect when an eloquent localization stores null over an inherited route field', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->routes('/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
    $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

    eloquentForgetRedirects();

    $fr->set('category', null)->save();

    $redirect = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();
    $saved = Entry::find($fr->id());

    expect($saved->has('category'))->toBeTrue()
        ->and($saved->get('category'))->toBeNull()
        ->and($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$fr->id()}");
});

it('creates a redirect when an eloquent localization route uses a field the origin route does not', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->routes([
        'en' => '/{slug}',
        'fr' => '/{category}/{slug}',
    ])->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->set('category', 'news'))->save();
    $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

    expect($en)->toBeInstanceOf(EloquentEntry::class)
        ->and($fr)->toBeInstanceOf(EloquentEntry::class);

    eloquentForgetRedirects();

    $en->set('category', 'updates')->save();

    $localization = Redirect::query()->where('site', 'fr')->where('source', '/news/article')->first();

    expect(Redirect::query()->where('site', 'en')->get())->toHaveCount(0)
        ->and($localization)->not->toBeNull()
        ->and($localization->destination())->toBe("entry::{$fr->id()}");
});

it('creates a redirect when an eloquent inherited localization date changes the url', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->dated(true)->routes('/{year}/{slug}')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2024-01-15'))->save();
    $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

    expect($en)->toBeInstanceOf(EloquentEntry::class)
        ->and($fr)->toBeInstanceOf(EloquentEntry::class);

    eloquentForgetRedirects();

    $en->date('2025-06-20')->save();

    $origin = Redirect::query()->where('site', 'en')->where('source', '/2024/post')->first();
    $localization = Redirect::query()->where('site', 'fr')->where('source', '/2024/article')->first();

    expect($origin?->destination())->toBe("entry::{$en->id()}")
        ->and($localization)->not->toBeNull()
        ->and($localization->destination())->toBe("entry::{$fr->id()}");
});

it('creates redirects when eloquent inherited localization date and route fields change together', function (string $model) {
    eloquentUseEntryModel($model);
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

    eloquentForgetRedirects();
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
})->with([
    'integer IDs' => [EntryModel::class],
    'UUIDs' => [UuidEntryModel::class],
]);

it('creates an entry redirect when an eloquent mount slug changes', function (bool $mountPublished) {
    Collection::make('sections')->routes('/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $blog = tap(Entry::make()->collection('sections')->locale('default')->slug('blog')->published($mountPublished))->save();

    Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
    Blink::flush();

    $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();

    eloquentForgetRedirects();

    $blog->slug('journal')->save();

    Blink::flush();

    $post = Entry::find($post->id());

    expect($blog)->toBeInstanceOf(EloquentEntry::class)
        ->and($post)->toBeInstanceOf(EloquentEntry::class)
        ->and(Entry::findByUri('/journal/hello', 'default')?->id())->toBe($post->id())
        ->and(Redirect::query()->where('site', 'default')->where('source', '/blog')->exists())->toBe($mountPublished)
        ->and(Redirect::query()->where('site', 'default')->where('source', '/blog/hello')->first()?->destination())->toBe("entry::{$post->id()}")
        ->and(Redirect::resolve('/blog/hello', 'default')->destination)->toBe($post->absoluteUrl());
})->with([
    'published mount' => [true],
    'draft mount' => [false],
]);

it('redirects published eloquent content when a draft mount inherits an origin route field change', function () {
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

    $path = resource_path('views/posts/tags/show.antlers.html');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '');

    try {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('design')->data(['title' => 'Design']))->save();

        eloquentForgetRedirects();

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
    } finally {
        File::delete($path);
    }
});

it('redirects eloquent content when a translated mount resolves to the edited site', function (string $site, string $oldMountPath, string $destination) {
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

    $path = resource_path('views/posts/tags/show.antlers.html');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '');

    try {
        tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('design')->data(['title' => 'Design']))->save();

        eloquentForgetRedirects();

        Entry::find($en->id())->in($site)->slug('updates')->save();

        Blink::flush();

        $redirects = Redirect::query()->where('site', $site)->get()->mapWithKeys(fn ($redirect) => [$redirect->source() => $redirect->destination()]);

        expect($redirects->get($oldMountPath.'/hello'))->toBe("entry::{$post->id()}")
            ->and(Entry::findByUri('/updates/hello', $site)?->id())->toBe($post->id())
            ->and(Redirect::resolve($oldMountPath.'/hello', $site)?->destination)->toBe($destination)
            ->and($redirects->get($oldMountPath.'/tags/design'))->toBe('/updates/tags/design')
            ->and(Collection::find('posts')->uri('de'))->toBe('/journal-de')
            ->and(Redirect::query()->where('site', 'de')->get())->toHaveCount(0);
    } finally {
        File::delete($path);
    }
})->with([
    'origin site' => ['en', '/blog', 'https://example.com/updates/hello'],
    'sibling translation' => ['fr', '/journal-fr', 'https://example.com/fr/updates/hello'],
]);

it('creates redirects through nested eloquent collection mounts', function () {
    Collection::make('sections')->routes('/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $blog = tap(Entry::make()->collection('sections')->locale('default')->slug('blog'))->save();
    Collection::make('posts')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
    Collection::make('articles')->routes('/{mount}/{slug}')->mount($blog->id())->sites(['default'])->saveQuietly();
    Blink::flush();

    $post = tap(Entry::make()->collection('posts')->locale('default')->slug('hello'))->save();
    $article = tap(Entry::make()->collection('articles')->locale('default')->slug('story'))->save();
    Collection::make('replies')->routes('/{mount}/{slug}')->mount($post->id())->sites(['default'])->saveQuietly();
    Blink::flush();

    $reply = tap(Entry::make()->collection('replies')->locale('default')->slug('one'))->save();

    eloquentForgetRedirects();

    $blog->slug('journal')->save();

    Blink::flush();

    expect($reply)->toBeInstanceOf(EloquentEntry::class)
        ->and(Entry::findByUri('/journal/hello', 'default')?->id())->toBe($post->id())
        ->and(Entry::findByUri('/journal/hello/one', 'default')?->id())->toBe($reply->id())
        ->and(Entry::findByUri('/journal/story', 'default')?->id())->toBe($article->id())
        ->and(Redirect::query()->where('site', 'default')->where('source', '/blog/hello/one')->first()?->destination())->toBe("entry::{$reply->id()}")
        ->and(Redirect::query()->where('site', 'default')->where('source', '/blog/story')->first()?->destination())->toBe("entry::{$article->id()}")
        ->and(Redirect::resolve('/blog/hello/one', 'default')?->destination)->toBe($reply->absoluteUrl());
});

it('keeps a stored localized date while capturing an inherited route field change', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->dated(true)->routes('/{year}/{category}/{slug}')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    $en = tap(Entry::make()->collection('articles')->locale('en')->slug('post')->date('2024-01-15')->set('category', 'news'))->save();
    $fr = tap($en->makeLocalization('fr')->slug('article'))->save();

    // Seed an existing override, independently of how the driver creates date overrides.
    $fr->model()->update([
        'date' => '2023-01-15',
        'data' => array_merge($fr->model()->getAttribute('data'), ['__localized_fields' => ['date']]),
    ]);
    Blink::flush();

    $fr = $en->descendants()->get('fr');

    expect($fr->date()->format('Y-m-d'))->toBe('2023-01-15');

    $en->date('2025-01-15')->set('category', 'updates');
    event(new EntrySaving($en));

    expect($fr->date()->format('Y-m-d'))->toBe('2023-01-15');
});

/** @param class-string<EntryModel> $model */
function eloquentUseEntryModel(string $model): void
{
    config(['statamic.eloquent-driver.entries.model' => $model]);

    if ($model !== EntryModel::class) {
        return;
    }

    Schema::drop('entries');

    $migration = require __DIR__.'/../../vendor/statamic/eloquent-driver/database/migrations/entries/2024_03_07_100000_create_entries_table.php';
    $migration->up();
}

function eloquentStructuredPages(bool $withBio = false): array
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

function eloquentSaveTree(array $tree): void
{
    Collection::find('pages')->structure()->in('default')->tree($tree)->save();
}

function eloquentForgetRedirects(): void
{
    Redirect::query()->get()->each->delete();
}
