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

    public function __construct()
    {
        throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);
        throw_unless($this->crawlingIsEnabled(), new NotFoundHttpException);
    }

    public function index(): SitemapIndex
    {
        return SitemapRepository::index();
    }

    public function show(string $type, string $handle): Sitemap
    {
        return throw_unless(SitemapRepository::find("{$type}::{$handle}"), NotFoundHttpException::class);
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
