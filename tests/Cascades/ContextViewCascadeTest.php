<?php

use Aerni\AdvancedSeo\Cascades\ContextViewCascade;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\LabeledValue;
use Statamic\Http\Middleware\AddViewPaths;
use Statamic\Tags\Context;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

it('returns taxonomy title with separator and site name', function () {
    $taxonomy = Taxonomy::find('tags');

    $context = new Context(collect([
        'page' => $taxonomy,
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->title())->toContain('Tags');
});

it('falls back to site name when fallback title has no page title', function () {
    $taxonomy = Taxonomy::find('tags');

    $context = new Context(collect([
        'page' => $taxonomy,
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->title())->toBe($cascade->siteName());
});

it('returns 404 title with separator and site name', function () {
    $context = new Context(collect([
        'response_code' => 404,
        'title' => 'Not Found',
        'current_url' => 'https://example.com/nonexistent',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->title())->toContain('404');
});

it('returns the locale from the current site', function () {
    Site::setCurrent('english');

    $context = new Context(collect([
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->locale())->toBe('en');
});

it('returns the canonical url from context current_url', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);
    Site::setCurrent('english');

    $context = new Context(collect([
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->canonical())->toBe('https://example.com/tags');
});

it('returns null hreflang when multisite is disabled', function () {
    config(['statamic.system.multisite' => false]);

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    $context = new Context(collect([
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->hreflang())->toBeNull();
});

it('returns null hreflang for non-taxonomy context', function () {
    $context = new Context(collect([
        'response_code' => 404,
        'title' => 'Not Found',
        'current_url' => 'https://example.com/nonexistent',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->hreflang())->toBeNull();
});

it('detects homepage from context is_homepage flag', function () {
    $context = new Context(collect([
        'title' => 'Home',
        'is_homepage' => true,
        'current_url' => 'https://example.com',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);
    $cascade->set('use_breadcrumbs', true);

    // Homepage should return null breadcrumbs.
    expect($cascade->breadcrumbs())->toBeNull();
});

it('resolves all breadcrumb ancestors on a site with a url prefix', function () {
    $collection = Collection::make('docs')
        ->routes('{parent_uri}/{slug}')
        ->sites(['english', 'german'])
        ->structureContents(['root' => true])
        ->save();

    // Rebuild the memoized SeoSet registry so the new collection is recognised.
    flushBlink();

    $home = Entry::make()->collection('docs')->locale('german')->slug('home')->data(['title' => 'Home']);
    $home->save();

    $levelOne = Entry::make()->collection('docs')->locale('german')->slug('level-1')->data(['title' => 'Level 1']);
    $levelOne->save();

    $levelTwo = Entry::make()->collection('docs')->locale('german')->slug('level-2')->data(['title' => 'Level 2']);
    $levelTwo->save();

    $collection->structure()->in('german')->tree([
        ['entry' => $home->id()],
        ['entry' => $levelOne->id(), 'children' => [
            ['entry' => $levelTwo->id()],
        ]],
    ])->save();

    flushBlink();

    Site::setCurrent('german');

    $context = new Context(collect([
        'current_url' => 'https://example.com/de/level-1/level-2',
        'site' => Site::get('german'),
    ]));

    $cascade = ContextViewCascade::from($context);
    $cascade->set('use_breadcrumbs', true);

    $items = collect(json_decode($cascade->breadcrumbs(), true)['itemListElement']);

    expect($items->pluck('name')->all())->toBe(['Home', 'Level 1', 'Level 2'])
        ->and($items->pluck('item')->all())->toBe([
            'https://example.com/de/',
            'https://example.com/de/level-1',
            'https://example.com/de/level-1/level-2',
        ]);
});

it('returns default title when context has a title', function () {
    $context = new Context(collect([
        'title' => 'Custom Page',
        'current_url' => 'https://example.com/custom',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    // For non-taxonomy, non-404 contexts, the title comes from cascade or context title.
    expect($cascade->title())->toContain('Custom Page');
});

it('resolves tokens in custom site schema against the view context', function () {
    config()->set('app.name', 'Acme Inc');

    $set = GlobalSet::make('company')->sites(['english']);
    $set->save();
    $set->in('english')->data(['tagline' => 'We build things'])->save();

    $context = new Context(collect([
        'title' => 'Custom Page',
        'current_url' => 'https://example.com/custom',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);
    $cascade->set('site_json_ld_type', new LabeledValue('custom', 'Custom'));
    $cascade->set('site_json_ld', '{"name": "{{ title }}", "url": "{{ current_url }}", "publisher": "{{ config:app:name }}", "slogan": "{{ company:tagline }}"}');

    expect($cascade->siteSchema())->toBe('{"name": "Custom Page", "url": "https://example.com/custom", "publisher": "Acme Inc", "slogan": "We build things"}');
});

it('restricts collection taxonomy hreflang and x-default to the collection sites', function (string $xDefaultSite, array $collectionSites, array $expected) {
    View::addLocation(__DIR__.'/../__fixtures__/views');
    config(['advanced-seo.crawling.environments' => ['testing']]);
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
        'french' => ['name' => 'French', 'url' => 'https://example.com/fr', 'locale' => 'fr'],
    ]);
    Site::setCurrent('german');
    $collection = Collection::make('blog')->routes('/blog/{slug}')->sites($collectionSites)->taxonomies(['tags']);
    $collection->saveQuietly();
    $taxonomy = Taxonomy::find('tags')->sites(['english', 'german', 'french']);
    $taxonomy->saveQuietly();
    $taxonomy->collection($collection);
    Seo::find('site::defaults')->config()->set('hreflang_x_default', $xDefaultSite)->save();
    flushBlink();

    $cascade = ContextViewCascade::from(new Context(collect([
        'page' => $taxonomy,
        'title' => 'Tags',
        'current_url' => 'https://example.com/de/blog/tags',
        'site' => Site::get('german'),
    ])));

    expect(collect($cascade->hreflang())->pluck('url', 'locale')->all())->toBe($expected);
})->with([
    'configured site and origin outside collection' => ['english', ['german', 'french'], [
        'de' => 'https://example.com/de/blog/tags',
        'fr' => 'https://example.com/fr/blog/tags',
    ]],
    'configured site inside collection' => ['french', ['german', 'french'], [
        'de' => 'https://example.com/de/blog/tags',
        'fr' => 'https://example.com/fr/blog/tags',
        'x-default' => 'https://example.com/fr/blog/tags',
    ]],
    'only one collection site' => ['german', ['german'], []],
]);

describe('taxonomy view availability', function () {
    beforeEach(function () {
        config(['advanced-seo.crawling.environments' => ['testing']]);
        Site::setSites([
            'english' => ['url' => 'https://example.com', 'locale' => 'en'],
            'german' => ['url' => 'https://example.com/de', 'locale' => 'de'],
            'french' => ['url' => 'https://example.com/fr', 'locale' => 'fr'],
        ]);
        Site::setCurrent('english');
        Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german', 'french'])->taxonomies(['tags'])->saveQuietly();
        Taxonomy::make('tags')->sites(['english', 'german', 'french'])->saveQuietly();
        Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
        flushBlink();

        $this->originalViewFinder = view()->getFinder();
        $this->viewDirectory = tempPath('head-taxonomy-views-'.Str::uuid());
        $finder = clone $this->originalViewFinder;
        $finder->setPaths([$this->viewDirectory]);
        $finder->flush();
        view()->setFinder($finder);

        foreach (['english', 'french'] as $site) {
            foreach (['tags/index', 'blog/tags/index'] as $template) {
                $path = $this->viewDirectory.'/'.$site.'/'.$template.'.antlers.html';
                File::ensureDirectoryExists(dirname($path));
                File::put($path, 'Taxonomy view');
            }
        }

    });

    afterEach(function () {
        view()->setFinder($this->originalViewFinder);
        File::deleteDirectory($this->viewDirectory);
    });

    it('uses only sites with resolvable views for head hreflang and x-default', function (string $requestSite, bool $sharedFallback, bool $collectionScoped) {
        Site::setCurrent($requestSite);

        if ($sharedFallback) {
            File::copyDirectory($this->viewDirectory.'/english', $this->viewDirectory);
        }

        app(AddViewPaths::class)->handle(request(), function () use ($requestSite, $sharedFallback, $collectionScoped) {
            $taxonomy = Taxonomy::find('tags');

            if ($collectionScoped) {
                $taxonomy->collection(Collection::find('blog'));
            }

            $path = $collectionScoped ? 'blog/tags' : 'tags';
            $cascade = ContextViewCascade::from(new Context(collect([
                'page' => $taxonomy,
                'title' => 'Tags',
                'current_url' => Site::get($requestSite)->absoluteUrl().'/'.$path,
                'site' => Site::get($requestSite),
            ])));
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

            expect(collect($cascade->hreflang())->pluck('url', 'locale')->all())->toBe($expected);

            return response('ok');
        });
    })->with([
        'English request, site-only views' => ['english', false],
        'German request, site-only views' => ['german', false],
        'English request, shared fallbacks' => ['english', true],
        'German request, shared fallbacks' => ['german', true],
    ])->with([
        'taxonomy' => false,
        'collection taxonomy' => true,
    ]);

    it('omits head hreflang when only one site has a resolvable view', function (bool $collectionScoped) {
        File::deleteDirectory($this->viewDirectory.'/french');
        $requestSite = 'english';
        $taxonomy = Taxonomy::find('tags');

        if ($collectionScoped) {
            $taxonomy->collection(Collection::find('blog'));
        }

        $path = $collectionScoped ? 'blog/tags' : 'tags';
        $cascade = ContextViewCascade::from(new Context(collect([
            'page' => $taxonomy,
            'title' => 'Tags',
            'current_url' => Site::get($requestSite)->absoluteUrl().'/'.$path,
            'site' => Site::get($requestSite),
        ])));
        expect($cascade->hreflang())->toBeNull();
    })->with([
        'taxonomy' => false,
        'collection taxonomy' => true,
    ]);
});

it('uses localized mounted collection paths for head hreflang', function (string $germanUrl) {
    config(['advanced-seo.crawling.environments' => ['testing']]);
    Site::setSites([
        'english' => ['url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['url' => $germanUrl, 'locale' => 'de'],
    ]);
    View::addLocation(__DIR__.'/../__fixtures__/views');
    Collection::make('mounts')->routes('/news/{slug}')->sites(['english', 'german'])->saveQuietly();
    $mount = Entry::make()->collection('mounts')->locale('english')->slug('articles');
    $mount->saveQuietly();
    $mount->makeLocalization('german')->slug('artikel')->saveQuietly();
    $collection = Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german'])->taxonomies(['tags'])->mount($mount->id());
    $collection->saveQuietly();
    $taxonomy = Taxonomy::find('tags')->collection($collection);
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
    flushBlink();
    Site::setCurrent('german');
    $cascade = ContextViewCascade::from(new Context(collect([
        'page' => $taxonomy,
        'current_url' => $germanUrl.'/news/artikel/tags',
        'site' => Site::get('german'),
    ])));

    $hreflang = $cascade->hreflang();

    expect(collect($hreflang)->pluck('url', 'locale')->all())->toBe([
        'en' => 'https://example.com/news/articles/tags',
        'de' => $germanUrl.'/news/artikel/tags',
        'x-default' => $germanUrl.'/news/artikel/tags',
    ])->and(Site::current()->handle())->toBe('german');
})->with([
    'site prefix' => ['https://example.com/de'],
    'separate domain' => ['https://example.de'],
]);
