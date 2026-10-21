<?php

use Aerni\AdvancedSeo\Cascades\ContentCascade;
use Aerni\AdvancedSeo\Cascades\ContextViewCascade;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\EntrySitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TermSitemapUrl;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesSitemap;
use Illuminate\Support\Facades\View;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Tags\Context;

uses(EnablesSitemap::class);

beforeEach(function () {
    Site::setSites([
        'dutch' => ['name' => 'Dutch', 'url' => 'https://example.com', 'locale' => 'nl'],
        'english' => ['name' => 'English', 'url' => 'https://example.com/en', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['dutch', 'english', 'german', 'french'])->saveQuietly();
});

it('uses the configured site when that localization exists', function () {
    xDefaultSite('english');

    $dutch = page('dutch', 'over-ons');
    $dutch->makeLocalization('english')->slug('about')->save();
    $dutch->makeLocalization('german')->slug('ueber-uns')->save();

    $english = $dutch->in('english');

    expect(hreflangUrl($dutch))->toBe('https://example.com/en/about')
        ->and(hreflangUrl($english))->toBe('https://example.com/en/about')
        ->and(sitemapHreflangUrl($dutch))->toBe('https://example.com/en/about')
        ->and(sitemapHreflangUrl($english))->toBe('https://example.com/en/about');
});

it('uses the statamic default site when x-default has not been saved', function () {
    $french = page('french', 'a-propos');
    $french->makeLocalization('dutch')->slug('over-ons')->save();

    expect(hreflangUrl($french))->toBe('https://example.com/over-ons')
        ->and(sitemapHreflangUrl($french->in('dutch')))->toBe('https://example.com/over-ons');
});

it('uses the original localization when the configured site has no indexable url', function () {
    xDefaultSite('english');

    $dutch = page('dutch', 'over-ons');
    $dutch->makeLocalization('german')->slug('ueber-uns')->save();
    $dutch->makeLocalization('french')->slug('a-propos')->save();

    expect(hreflangUrl($dutch))->toBe('https://example.com/over-ons')
        ->and(hreflangUrl($dutch->in('german')))->toBe('https://example.com/over-ons')
        ->and(sitemapHreflangUrl($dutch->in('french')))->toBe('https://example.com/over-ons');
});

it('uses the original localization of a page created outside the default site', function () {
    xDefaultSite('english');

    $french = page('french', 'a-propos');
    $french->makeLocalization('german')->slug('ueber-uns')->save();

    expect(hreflangUrl($french))->toBe('https://example.com/fr/a-propos')
        ->and(hreflangUrl($french->in('german')))->toBe('https://example.com/fr/a-propos')
        ->and(sitemapHreflangUrl($french->in('german')))->toBe('https://example.com/fr/a-propos');
});

it('omits x-default when the original localization is not indexable', function () {
    xDefaultSite('english');

    $dutch = page('dutch', 'over-ons');
    $german = $dutch->makeLocalization('german')->slug('ueber-uns');
    $french = $dutch->makeLocalization('french')->slug('a-propos');
    $german->save();
    $french->save();

    $dutch->set('seo_noindex', true)->save();
    $german->set('seo_noindex', false)->save();
    $french->set('seo_noindex', false)->save();

    expect(hreflangLocales($german))->toContain('de')->toContain('fr')->not->toContain('x-default')
        ->and(hreflangLocales($french))->not->toContain('x-default')
        ->and(sitemapHreflangLocales($german))->toContain('de')->not->toContain('x-default');
});

it('falls back to the default site when the saved x-default site does not exist', function () {
    xDefaultSite('missing');

    $french = page('french', 'a-propos');
    $french->makeLocalization('dutch')->slug('over-ons')->save();
    $french->makeLocalization('english')->slug('about')->save();

    expect(hreflangUrl($french))->toBe('https://example.com/over-ons')
        ->and(sitemapHreflangUrl($french->in('english')))->toBe('https://example.com/over-ons');
});

it('uses the term default locale when the configured site is not in the cluster', function () {
    xDefaultSite('english');

    Taxonomy::make('tags')->sites(['dutch', 'german'])->saveQuietly();

    $term = Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data(['title' => 'PHP']);
    $term->save();
    $german = $term->in('german')->slug('php')->data(['title' => 'PHP']);
    $german->save();

    expect(hreflangUrl($term))->toBe('https://example.com/tags/php')
        ->and(hreflangUrl($german))->toBe('https://example.com/tags/php')
        ->and(sitemapHreflangUrl($german))->toBe('https://example.com/tags/php');
});

it('uses the configured site for a taxonomy index', function () {
    xDefaultSite('english');
    Site::setCurrent('dutch');

    Taxonomy::make('tags')->sites(['dutch', 'english', 'german'])->saveQuietly();

    $cascade = taxonomyCascade(Taxonomy::find('tags'));

    expect(hreflangUrl($cascade))->toBe('https://example.com/en/tags')
        ->and(taxonomySitemapHreflangUrl('dutch'))->toBe('https://example.com/en/tags');
});

it('uses the first taxonomy site when the configured site is not available', function () {
    xDefaultSite('english');
    Site::setCurrent('german');

    Taxonomy::make('tags')->sites(['german', 'french'])->saveQuietly();

    $cascade = taxonomyCascade(Taxonomy::find('tags'));

    expect(hreflangUrl($cascade))->toBe('https://example.com/de/tags')
        ->and(taxonomySitemapHreflangUrl('french'))->toBe('https://example.com/de/tags');
});

function xDefaultSite(string $site): void
{
    Seo::find('site::defaults')->config()->set('hreflang_x_default', $site)->save();
    flushBlink();
}

function page(string $site, string $slug, array $data = []): Entry
{
    $entry = EntryFacade::make()
        ->collection('pages')
        ->locale($site)
        ->slug($slug)
        ->data(['title' => $slug, ...$data]);

    $entry->save();

    return $entry;
}

function hreflangUrl(object $model): ?string
{
    $alternate = collect(hreflang($model))->firstWhere('locale', 'x-default');

    return $alternate['url'] ?? null;
}

function hreflangLocales(object $model): array
{
    return collect(hreflang($model))->pluck('locale')->all();
}

function hreflang(object $model): ?array
{
    if ($model instanceof ContextViewCascade) {
        return $model->hreflang();
    }

    return ContentCascade::from($model)->hreflang();
}

function sitemapHreflangUrl(object $model): ?string
{
    $alternate = collect(sitemapAlternates($model))->firstWhere('hreflang', 'x-default');

    return $alternate['href'] ?? null;
}

function sitemapHreflangLocales(object $model): array
{
    return collect(sitemapAlternates($model))->pluck('hreflang')->all();
}

function sitemapAlternates(object $model): ?array
{
    $url = $model instanceof Entry
        ? new EntrySitemapUrl($model)
        : new TermSitemapUrl($model);

    return $url->alternates();
}

function taxonomyCascade(object $taxonomy): ContextViewCascade
{
    return ContextViewCascade::from(new Context(collect([
        'page' => $taxonomy,
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::current(),
    ])));
}

function taxonomySitemapHreflangUrl(string $site): ?string
{
    View::addLocation(__DIR__.'/../__fixtures__/views');

    $sitemap = Sitemap::index($site)->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');

    $url = new TaxonomySitemapUrl(Taxonomy::find('tags'), $site);
    $url->sitemap($sitemap);

    return collect($url->alternates())->firstWhere('hreflang', 'x-default')['href'] ?? null;
}
