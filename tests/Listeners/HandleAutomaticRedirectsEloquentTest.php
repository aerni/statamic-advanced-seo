<?php

use Aerni\AdvancedSeo\Eloquent\Redirect as EloquentRedirect;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesEloquentEntries;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Facades\File;
use Statamic\Eloquent\Entries\Entry as EloquentEntry;
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

it('creates a redirect when an eloquent entry route field changes the url', function () {
    Collection::make('posts')->routes('/{category}/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('category', 'news'))->save();

    $entry->set('category', 'updates')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
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

it('creates a redirect when an eloquent entry moves in the tree', function () {
    [$home, $about, $team] = eloquentStructuredPages();

    expect($team)->toBeInstanceOf(EloquentEntry::class);

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
        ->and($redirect->destination())->toBe("entry::{$team->id()}");
});

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

it('creates a redirect for an eloquent child page when a parent slug changes', function () {
    [$home, $about, $team] = eloquentStructuredPages();

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
        ->and(Entry::find($team->id())->model()->fresh()->getAttribute('uri'))->toBe('/company/team');
});

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
