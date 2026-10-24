<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TermSitemapUrl;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Http\Middleware\AddViewPaths;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english'])->saveQuietly();
});

it('has a type of taxonomy', function () {
    $sitemap = Sitemap::index('english')->sitemaps()->first(fn ($s) => $s instanceof TaxonomySitemap);

    expect($sitemap->type())->toBe('taxonomy');
});

it('uses the taxonomy handle', function () {
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    expect($sitemap)->not->toBeNull()
        ->and($sitemap->handle())->toBe('tags');
});

it('returns a collection of urls', function () {
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap);

    expect($sitemap->urls())->toBeInstanceOf(Illuminate\Support\Collection::class);
});

it('creates sitemaps for all taxonomies', function () {
    $index = Sitemap::index('english');

    $taxonomySitemaps = $index->sitemaps()
        ->filter(fn ($s) => $s instanceof TaxonomySitemap)
        ->pluck(null)
        ->map->handle()
        ->values();

    expect($taxonomySitemaps)->toContain('tags')
        ->toContain('categories');
});

it('excludes terms whose template view does not exist', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');

    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')
        ->data(['title' => 'PHP', 'template' => 'nonexistent-template'])->save();
    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('laravel')
        ->data(['title' => 'Laravel'])->save();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    $termLocs = $sitemap->urls()
        ->filter(fn ($url) => $url instanceof TermSitemapUrl)
        ->map->loc();

    expect($termLocs)->toContain('https://example.com/tags/laravel')
        ->and($termLocs)->not->toContain('https://example.com/tags/php');
});

describe('site-specific views', function () {
    beforeEach(function () {
        $this->originalViewFinder = view()->getFinder();
        $this->viewDirectory = tempPath('taxonomy-views-'.Str::uuid());
        $finder = clone $this->originalViewFinder;
        $finder->setPaths([$this->viewDirectory]);
        $finder->flush();
        view()->setFinder($finder);
        config(['advanced-seo.sitemap.path' => $this->viewDirectory.'/sitemaps']);

        Site::setSites([
            'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
            'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
            'french' => ['name' => 'French', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);
        Site::setCurrent('english');
        Taxonomy::find('tags')->sites(['english', 'german', 'french'])->saveQuietly();
        Collection::find('blog')->sites(['english', 'german', 'french'])->taxonomies(['tags'])->saveQuietly();
        $term = Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data(['title' => 'PHP']);
        $term->save();

        foreach (['english', 'german', 'french'] as $site) {
            $term->in($site)->data(['title' => 'PHP'])->save();
            Entry::make()->collection('blog')->locale($site)->slug('post')
                ->data(['title' => 'Post', 'tags' => ['php']])->save();
        }

        foreach (['english', 'french'] as $site) {
            foreach (['tags/index', 'tags/show', 'blog/tags/index', 'blog/tags/show'] as $template) {
                $path = $this->viewDirectory.'/'.$site.'/'.$template.'.antlers.html';
                File::ensureDirectoryExists(dirname($path));
                File::put($path, 'Taxonomy view');
            }
        }

        Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
        flushBlink();
    });

    afterEach(function () {
        view()->setFinder($this->originalViewFinder);
        File::deleteDirectory($this->viewDirectory);
    });

    it('checks each site independently for listings, alternates, and x-default', function (string $requestSite, bool $sharedFallback) {
        Site::setCurrent($requestSite);

        if ($sharedFallback) {
            File::copyDirectory($this->viewDirectory.'/english', $this->viewDirectory);
        }

        app(AddViewPaths::class)->handle(request(), function () use ($sharedFallback) {
            view()->exists('tags.index');
            $finder = view()->getFinder();
            $paths = $finder->getPaths();
            $views = $finder->getViews();
            $sitemap = Sitemap::index('english')->sitemaps()
                ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');

            $expectedLocales = $sharedFallback ? ['english', 'german', 'french'] : ['english', 'french'];
            expect($sitemap->taxonomies()->keys()->all())->toBe($expectedLocales)
                ->and($sitemap->collectionTaxonomies()->pluck('site')->all())->toBe($expectedLocales)
                ->and($sitemap->collectionTerms()->map->locale()->sort()->values()->all())->toBe(
                    $sharedFallback ? ['english', 'french', 'german'] : ['english', 'french']
                );

            foreach (['tags', 'blog/tags', 'blog/tags/php'] as $path) {
                $url = $sitemap->urls()->first(fn ($url) => $url->loc() === 'https://example.com/'.$path);
                $expected = [
                    'en' => 'https://example.com/'.$path,
                    'fr' => 'https://example.com/fr/'.$path,
                    'x-default' => 'https://example.com/'.$path,
                ];

                if ($sharedFallback) {
                    $expected = [
                        'en' => 'https://example.com/'.$path,
                        'de' => 'https://example.com/de/'.$path,
                        'fr' => 'https://example.com/fr/'.$path,
                        'x-default' => 'https://example.com/de/'.$path,
                    ];
                }

                expect($url)->not->toBeNull($path)
                    ->and(collect($url->alternates())->pluck('href', 'hreflang')->all())->toBe($expected);
            }

            expect($sitemap->urls()->reject(fn ($url) => $url instanceof TermSitemapUrl))->toHaveCount($sharedFallback ? 9 : 6)
                ->and(view()->getFinder())->toBe($finder)
                ->and($finder->getPaths())->toBe($paths)
                ->and($finder->getViews())->toBe($views);

            return response('ok');
        });
    })->with([
        'English request, site-only views' => ['english', false],
        'German request, site-only views' => ['german', false],
        'English request, shared fallbacks' => ['english', true],
        'German request, shared fallbacks' => ['german', true],
    ]);

    it('checks ordinary term views for both listings and alternates', function () {
        Collection::find('blog')->taxonomies([])->saveQuietly();
        flushBlink();

        $sitemap = Sitemap::index('english')->sitemaps()
            ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
        $urls = $sitemap->urls()->filter(fn ($url) => $url instanceof TermSitemapUrl);
        $url = $urls->first(fn ($url) => $url->site() === 'english');

        expect($urls->map->loc()->values()->all())->toEqualCanonicalizing([
            'https://example.com/tags/php',
            'https://example.com/fr/tags/php',
        ])->and(collect($url->alternates())->pluck('href', 'hreflang')->all())->toBe([
            'en' => 'https://example.com/tags/php',
            'fr' => 'https://example.com/fr/tags/php',
            'x-default' => 'https://example.com/tags/php',
        ]);
    });

    it('generates site-specific taxonomy URLs from the CLI without HTTP view paths', function () {
        Taxonomy::find('categories')->sites(['english', 'german', 'french'])->template('tags.index')->saveQuietly();
        flushBlink();

        $this->artisan('seo:generate-sitemaps')->assertSuccessful();

        $xml = simplexml_load_string(File::get(Sitemap::path('example.com', 'taxonomy-categories.xml')));
        $xml->registerXPathNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

        expect(array_map(fn ($loc) => (string) $loc, $xml->xpath('//sitemap:loc')))->toEqualCanonicalizing([
            'https://example.com/categories',
            'https://example.com/fr/categories',
        ])->and($xml->xpath('//xhtml:link[@hreflang="de"]'))->toBe([])
            ->and(array_map(fn ($href) => (string) $href, $xml->xpath('//xhtml:link[@hreflang="x-default"]/@href')))
            ->toBe(['https://example.com/categories', 'https://example.com/categories']);
    });
});
