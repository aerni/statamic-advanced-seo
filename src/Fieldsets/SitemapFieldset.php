<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Facades\Fieldset;
use Aerni\AdvancedSeo\Fieldsets\BaseFieldset;
use Illuminate\Support\Collection;

class SitemapFieldset extends BaseFieldset
{
    protected string $display = 'Sitemap';

    protected function sections(): array
    {
        return [
            $this->sitemap(),
        ];
    }

    protected function sitemap(): ?Collection
    {
        return config('advanced-seo.sitemap', true)
            ? Fieldset::find('sitemap')
            : null;
    }
}
