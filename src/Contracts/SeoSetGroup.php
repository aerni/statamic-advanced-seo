<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface SeoSetGroup
{
    public function seoSets(): Collection;

    public function type(): string;

    public function title(): string;

    public function indexUrl(): string;

    public function icon(): string;
}
