<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Enums\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

class DefaultsData
{
    public function __construct(
        public string $type,
        public string $handle,
        public Context $context,
        public ?string $locale = null,
        public ?Collection $sites = null,
    ) {}

    public function id(): string
    {
        return "{$this->type}::{$this->handle}::{$this->locale}";
    }

    public function set(): ?SeoSet
    {
        return Seo::find("{$this->type}::{$this->handle}");
    }

    public function isConfigContext(): bool
    {
        return $this->context === Context::CONFIG;
    }

    public function isLocalizationContext(): bool
    {
        return $this->context === Context::LOCALIZATION;
    }

    public function isContentContext(): bool
    {
        return $this->context === Context::CONTENT;
    }
}
