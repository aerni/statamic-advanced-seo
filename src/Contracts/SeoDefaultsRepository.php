<?php

namespace Aerni\AdvancedSeo\Contracts;

use Statamic\Data\DataCollection;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;

interface SeoDefaultsRepository
{
    public function make(): SeoDefaultSet;

    public function find(string $type, string $id): ?SeoDefaultSet;

    public function all(): Collection;

    public function allOfType(string $type): DataCollection;

    public function save(SeoDefaultSet $set): self;

    public function delete(SeoDefaultSet $set): bool;
}
