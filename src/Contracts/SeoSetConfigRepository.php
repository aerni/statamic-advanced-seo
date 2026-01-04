<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface SeoSetConfigRepository
{
    public function make(): SeoSetConfig;

    public function find(string $id): ?SeoSetConfig;

    public function findOrMake(string $id): SeoSetConfig;

    public function all(): Collection;

    public function save(SeoSetConfig $config): void;

    public function delete(SeoSetConfig $config): void;
}
