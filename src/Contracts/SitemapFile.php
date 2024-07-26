<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SitemapFile
{
    public function filename(): string;

    public function file(): ?string;

    public function path(): string;

    public function save(): self;
}
