<?php

namespace Aerni\AdvancedSeo\Context;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;

class Context
{
    public function __construct(
        public string $type,
        public string $handle,
        public Scope $scope,
        public ?string $site = null,
    ) {}

    /**
     * Create a Context instance from various model types.
     */
    public static function from(mixed $model): ?self
    {
        return new ContextResolver($model)->resolve();
    }

    /**
     * Get the unique identifier for this context including the site.
     */
    public function id(): string
    {
        return "{$this->type}::{$this->handle}::{$this->site}";
    }

    /**
     * Get the associated SeoSet.
     */
    public function seoSet(): ?SeoSet
    {
        return Seo::find("{$this->type}::{$this->handle}");
    }

    /**
     * Check if this is a config scope context.
     */
    public function isConfig(): bool
    {
        return $this->scope === Scope::CONFIG;
    }

    /**
     * Check if this is a localization scope context.
     */
    public function isLocalization(): bool
    {
        return $this->scope === Scope::LOCALIZATION;
    }

    /**
     * Check if this is a content scope context.
     */
    public function isContent(): bool
    {
        return $this->scope === Scope::CONTENT;
    }
}
