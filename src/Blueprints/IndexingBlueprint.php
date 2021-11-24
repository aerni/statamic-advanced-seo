<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\SitemapFields;
use Aerni\AdvancedSeo\Fields\CrawlingFields;
use Aerni\AdvancedSeo\Fields\KnowledgeGraphFields;
use Aerni\AdvancedSeo\Fields\SiteVerificationFields;

class IndexingBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'indexing';
    }

    protected function sections(): array
    {
        return [
            'crawling' => CrawlingFields::class,
            'sitemap' => SitemapFields::class,
            'knowledge_graph' => KnowledgeGraphFields::class,
            'site_verification' => SiteVerificationFields::class,
        ];
    }
}
