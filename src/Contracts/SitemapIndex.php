<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\Domain;
use Illuminate\Support\Collection;

interface SitemapIndex
{
    public function domain(): Domain;

    public function sites(): Collection;

    public function add(Sitemap $sitemap): self;

    public function sitemaps(): Collection;
}
