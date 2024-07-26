<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Contracts\Sitemap;

interface SitemapIndex
{
    public function add(Sitemap $sitemap): self;

    public function sitemaps(): Collection;
}
