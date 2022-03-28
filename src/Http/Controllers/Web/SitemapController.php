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

        // $view = Cache::remember('advanced-seo::sitemaps::index', config('advanced-seo.sitemap.expiry', 60), function () {
        $view = view('advanced-seo::sitemaps.index', [
            'sitemaps' => Sitemap::all(),
            'version' => Addon::get('aerni/advanced-seo')->version(),
        ])->render();
        // });

        return response($view)->header('Content-Type', 'text/xml');
    }

    public function show(string $type, string $handle): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        throw_unless($sitemap = Sitemap::find("{$type}::{$handle}"), new NotFoundHttpException);

        $sitemapUrls = $sitemap->urls();

        throw_unless($sitemapUrls->isNotEmpty(), new NotFoundHttpException);

        // $view = Cache::remember("advanced-seo::sitemaps::{$type}::{$handle}", config('advanced-seo.sitemap.expiry', 60), function () use ($sitemapUrls) {
        $view = view('advanced-seo::sitemaps.show', [
            'urls' => $sitemapUrls,
            'version' => Addon::get('aerni/advanced-seo')->version(),
        ])->render();
        // });

        return response($view)->header('Content-Type', 'text/xml');
    }

    public function xsl(): Response
    {
        $path = __DIR__ . '/../../../../resources/xsl/sitemap.xsl';

        return response(file_get_contents($path))->header('Content-Type', 'text/xsl');
    }
}
