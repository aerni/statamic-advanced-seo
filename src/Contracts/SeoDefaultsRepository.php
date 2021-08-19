<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\SeoDefault;
use Aerni\AdvancedSeo\Stache\SeoQueryBuilder;
use Statamic\Data\DataCollection;

interface SeoDefaultsRepository
{
    public function make(): SeoDefault;

    public function all(): DataCollection;

    public function find(string $type, string $handle): ?SeoDefault;

    public function whereType(string $handle): DataCollection;

    public function save(SeoDefault $default): self;

    public function delete(SeoDefault $default): bool;

    public function query(): SeoQueryBuilder;
}
