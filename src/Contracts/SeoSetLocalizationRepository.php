<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface SeoSetLocalizationRepository
{
    public function make(): SeoSetLocalization;

    public function find(string $id): ?SeoSetLocalization;

    public function all(): Collection;

    public function whereSeoSet(string $id): Collection;

    public function save(SeoSetLocalization $localization): void;

    public function delete(SeoSetLocalization $localization): void;
}
