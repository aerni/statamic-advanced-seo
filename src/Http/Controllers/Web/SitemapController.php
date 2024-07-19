<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Addon;

class SitemapController extends Controller
{
    public function index(): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $sitemaps = Cache::remember('advanced-seo::sitemaps::index', Sitemap::cacheExpiry(), function () {
            return Sitemap::all()
                ->filter(fn ($sitemap) => $sitemap->urls()->isNotEmpty())
                ->toArray();
        });

        throw_unless($sitemaps, new NotFoundHttpException);

        return response()
            ->view('advanced-seo::sitemaps.index', [
                'sitemaps' => $sitemaps,
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ])
            ->header('Content-Type', 'text/xml')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function show(string $type, string $handle): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $id = "{$type}::{$handle}";

        $urls = Cache::remember(
            "advanced-seo::sitemaps::{$id}",
            Sitemap::cacheExpiry(),
            fn () => Sitemap::find($id)?->urls()
        );

        throw_unless($urls, new NotFoundHttpException);

        return response()
            ->view('advanced-seo::sitemaps.show', [
                'urls' => $urls,
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ])
            ->header('Content-Type', 'text/xml')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function xsl(): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $path = __DIR__.'/../../../../resources/views/sitemaps/sitemap.xsl';

        return response(file_get_contents($path))
            ->header('Content-Type', 'text/xsl')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
