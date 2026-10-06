<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesEloquentEntries;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Eloquent\Entries\Entry as EloquentEntry;
use Statamic\Eloquent\Taxonomies\Term as EloquentTerm;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesEloquentEntries::class, EnablesRedirects::class, RefreshDatabase::class);

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

it('creates a redirect when an eloquent entry slug changes', function () {
    Collection::make('articles')->routes('/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('articles')->locale('default')->slug('old'))->save();

    expect($entry)->toBeInstanceOf(EloquentEntry::class);

    $entry->slug('new')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/old')->first();

    expect($redirect)->not->toBeNull()
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

it('creates a redirect when an eloquent entry route field changes the url', function () {
    Collection::make('posts')->routes('/{category}/{slug}')->sites(['default'])->saveQuietly();
    Blink::flush();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->set('category', 'news'))->save();

    $entry->set('category', 'updates')->save();

    $redirect = Redirect::query()->where('site', 'default')->where('source', '/news/post')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates a redirect when an eloquent term localization slug changes', function () {
    Site::setSites([
        'en' => ['name' => 'EN', 'url' => 'https://example.com', 'locale' => 'en'],
        'fr' => ['name' => 'FR', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Taxonomy::make('topics')->sites(['en', 'fr'])->saveQuietly();
    Blink::flush();

    Term::make()->taxonomy('topics')->inDefaultLocale()->slug('old')->data(['title' => 'Old'])->save();

    expect(Term::find('topics::old'))->toBeInstanceOf(EloquentTerm::class);

    Term::find('topics::old')->in('fr')->slug('vieux')->save();

    eloquentForgetRedirects();

    Term::find('topics::old')->in('fr')->slug('ancien')->save();

    $fr = Redirect::query()->where('site', 'fr')->where('source', '/topics/vieux')->first();

    expect($fr)->not->toBeNull()
        ->and($fr->destination())->toBe('/topics/ancien')
        ->and(Redirect::query()->where('site', 'en')->get())->toHaveCount(0);
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

it('creates redirects for eloquent descendants when their parent moves', function () {
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

it('creates a redirect for an eloquent descendant when a parent slug changes', function () {
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
