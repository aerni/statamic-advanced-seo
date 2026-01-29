<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapIndex;
use Aerni\AdvancedSeo\Facades\Sitemap as SitemapRegistry;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;

class SitemapController extends Controller
{
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
