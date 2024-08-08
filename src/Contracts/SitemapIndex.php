<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface SitemapIndex
{
    public function add(Sitemap $sitemap): self;

    public function sitemaps(): Collection;
}
