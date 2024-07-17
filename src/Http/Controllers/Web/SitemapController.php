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

        $view = Cache::remember('advanced-seo::sitemaps::index', Sitemap::cacheExpiry(), function () {
            return view('advanced-seo::sitemaps.index', [
                'sitemaps' => Sitemap::all(),
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ])->render();
        });

        return response($view)->withHeaders([
            'Content-Type' => 'text/xml',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function show(string $type, string $handle): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        throw_unless($sitemap = Sitemap::find("{$type}::{$handle}"), new NotFoundHttpException);

        $view = Cache::remember("advanced-seo::sitemaps::{$type}::{$handle}", Sitemap::cacheExpiry(), function () use ($sitemap) {
            $urls = $sitemap->urls();

            throw_unless($urls->isNotEmpty(), new NotFoundHttpException);

            return view('advanced-seo::sitemaps.show', [
                'urls' => $urls,
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ])->render();
        });

        return response($view)->withHeaders([
            'Content-Type' => 'text/xml',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function xsl(): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $path = __DIR__.'/../../../../resources/views/sitemaps/sitemap.xsl';

        return response(file_get_contents($path))->withHeaders([
            'Content-Type' => 'text/xsl',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
