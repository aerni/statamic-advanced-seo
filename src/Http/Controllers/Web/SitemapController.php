<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapIndex;
use Aerni\AdvancedSeo\Facades\Sitemap as SitemapRepository;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Statamic\Exceptions\NotFoundHttpException;

class SitemapController extends Controller
{
    public function index(): SitemapIndex
    {
        return SitemapRepository::index();
    }

    public function show(string $id): Sitemap
    {
        return throw_unless(SitemapRepository::find($id), NotFoundHttpException::class);
    }

    public function xsl(): Response
    {
        return response(
            content: SitemapRepository::xsl(),
            headers: [
                'Content-Type' => 'text/xsl',
                'X-Robots-Tag' => 'noindex, nofollow',
            ],
        );
    }
}
