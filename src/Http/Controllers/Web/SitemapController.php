<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapIndex;
use Aerni\AdvancedSeo\Facades\Sitemap as SitemapRegistry;
use Aerni\AdvancedSeo\Features\Sitemap as SitemapFeature;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;

class SitemapController extends Controller
{
    public function __construct()
    {
        throw_unless(SitemapFeature::enabled(), new NotFoundHttpException);
        throw_unless(in_array(app()->environment(), config('advanced-seo.crawling.environments', [])), new NotFoundHttpException);
    }

    public function index(): SitemapIndex
    {
        return throw_unless(SitemapRegistry::index(Site::current()->handle()), NotFoundHttpException::class);
    }

    public function show(string $id): Sitemap
    {
        return throw_unless(SitemapRegistry::index(Site::current()->handle())?->find($id), NotFoundHttpException::class);
    }

    public function xsl(): Response
    {
        return response(
            content: SitemapRegistry::xsl(),
            headers: [
                'Content-Type' => 'text/xsl',
                'X-Robots-Tag' => 'noindex, nofollow',
            ],
        );
    }
}
