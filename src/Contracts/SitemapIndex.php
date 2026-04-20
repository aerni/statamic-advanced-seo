<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Sitemaps\Domain;
use Illuminate\Support\Collection;

interface SitemapIndex
{
    public function domain(): Domain;

    public function sites(): Collection;

    public function sitemaps(): Collection;
}
