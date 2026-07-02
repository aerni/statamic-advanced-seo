<?php

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);

    Taxonomy::make('tags')->sites(['default'])->saveQuietly();
});

it('creates a permanent redirect when a term slug changes', function () {
    $term = tap(Term::make()->taxonomy('tags')->inDefaultLocale()->slug('old')->data(['title' => 'Old']))->save();

    $term->slug('new')->save();

    $redirect = Redirects::query()->where('site', 'default')->where('source', '/tags/old')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe('/tags/new')
        ->and($redirect->type())->toBe(RedirectType::Permanent)
        ->and($redirect->enabled())->toBeTrue()
        ->and($redirect->description())->toBe('Created automatically because the URL changed.');
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
