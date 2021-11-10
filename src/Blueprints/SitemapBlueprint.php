<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\SitemapFields;

class SitemapBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'sitemap';
    }

    protected function sections(): array
    {
        return [
            'sitemap' => SitemapFields::class,
        ];
    }
}
