<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Addon;

class SitemapController extends Controller
{
    protected array $headers = [
        'Content-Type' => 'text/xml',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    public function index(): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        return response()->withHeaders($this->headers)
            ->view('advanced-seo::sitemap.index', [
                'sitemaps' => Sitemap::all(),
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ]);
    }

    public function show(string $type, string $handle): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        throw_unless($sitemap = Sitemap::find("{$type}::{$handle}"), new NotFoundHttpException);

        return response()->withHeaders($this->headers)
            ->view('advanced-seo::sitemap.show', [
                'sitemap' => $sitemap,
                'version' => Addon::get('aerni/advanced-seo')->version(),
            ]);
    }

    public function xsl(): Response
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        $content = file_get_contents(__DIR__.'/../../../../resources/views/sitemap/sitemap.xsl');

        return response($content)->withHeaders($this->headers);
    }
}
