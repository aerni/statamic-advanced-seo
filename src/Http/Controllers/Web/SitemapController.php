<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapIndex;
use Aerni\AdvancedSeo\Facades\Sitemap as SitemapRepository;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Statamic\Exceptions\NotFoundHttpException;

class SitemapController extends Controller
{
    use EvaluatesIndexability;

    public function index(): SitemapIndex
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);
        throw_unless($this->crawlingIsEnabled(), new NotFoundHttpException);

        return SitemapRepository::index();
    }

    public function show(string $id): Sitemap
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);
        throw_unless($this->crawlingIsEnabled(), new NotFoundHttpException);

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
