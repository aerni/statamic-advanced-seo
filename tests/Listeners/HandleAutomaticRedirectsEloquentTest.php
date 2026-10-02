<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesEloquentEntries;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Eloquent\Entries\Entry as EloquentEntry;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
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
