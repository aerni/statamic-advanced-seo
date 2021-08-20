<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Stache\SeoQueryBuilder;
use Statamic\Data\DataCollection;

interface SeoDefaultsRepository
{
    public function make(): SeoDefaultSet;

    public function all(): DataCollection;

    public function find(string $type, string $handle): ?SeoDefaultSet;

    public function save(SeoDefaultSet $default): self;

    public function delete(SeoDefaultSet $default): bool;

    public function query(): SeoQueryBuilder;
}
