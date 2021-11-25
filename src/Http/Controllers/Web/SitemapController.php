<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;

class SitemapController extends Controller
{
    public function index(): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $site = Site::current();

        $view = Cache::remember("advanced-seo::sitemaps::{$site->handle()}", config('advanced-seo.sitemap.expiry'), function () use ($site) {
            return view('advanced-seo::sitemaps.index', [
                'xmlDefinition' => '<?xml version="1.0" encoding="utf-8"?>',
                'xslLink' => '<?xml-stylesheet type="text/xsl" href="' . $site->absoluteUrl() . '/sitemap.xsl"?>',
                'sitemaps' => Sitemap::whereSite($site->handle()),
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ])->render();
        });

        return response($view)->header('Content-Type', 'text/xml');
    }

    public function show(string $type, string $handle): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $site = Site::current();

        throw_unless($sitemap = Sitemap::find($type, $handle, $site->handle()), new NotFoundHttpException);

        $view = Cache::remember("advanced-seo::sitemaps::{$site->handle()}::{$type}::{$handle}", config('advanced-seo.sitemap.expiry'), function () use ($site, $sitemap) {
            return view('advanced-seo::sitemaps.show', [
                'xmlDefinition' => '<?xml version="1.0" encoding="utf-8"?>',
                'xslLink' => '<?xml-stylesheet type="text/xsl" href="' . $site->absoluteUrl() . '/sitemap.xsl"?>',
                'data' => $sitemap->items(),
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ])->render();
        });

        return response($view)->header('Content-Type', 'text/xml');
    }

    public function xsl(): Response
    {
        $path = __DIR__ . '/../../../../resources/xsl/sitemap.xsl';

        return response(file_get_contents($path))->header('Content-Type', 'text/xsl');
    }
}
