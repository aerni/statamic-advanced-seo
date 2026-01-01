<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Illuminate\Support\Collection;

interface SeoSet
{
    public function id(): string;

    public function type(): string;

    public function handle(): string;

    public function title(): string;

    public function icon(): string;

    public function blueprint(string $blueprint): ?string;

    public function defaultValues(): Collection;

    public function enabled(): bool;

    public function config(): SeoSetConfig;

    public function origins(): Collection;

    public function sites(): Collection;

    public function localizations(): Collection;

    public function selectedSite(): string;

    public function in(string $locale): ?SeoSetLocalization;

    public function inSelectedSite(): ?SeoSetLocalization;

    public function inDefaultSite(): ?SeoSetLocalization;

    public function save(): self;

    public function delete(): bool;

    public function defaultsData(?DefaultsData $data = null): DefaultsData|self;
}
