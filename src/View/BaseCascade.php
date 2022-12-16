<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Actions\GetContentDefaults;
use Aerni\AdvancedSeo\Actions\GetPageData;
use Aerni\AdvancedSeo\Actions\GetSiteDefaults;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Illuminate\Support\Collection;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Augmented;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Data\ContainsData;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Support\Str;
use Statamic\Tags\Context;

abstract class BaseCascade implements Augmentable
{
    use HasAugmentedInstance;
    use ContainsData;

    public function __construct(protected Context|DefaultsData|Entry|Term $model)
    {
        $this->data = collect();
    }

    public static function from(mixed $model): self
    {
        return (new static($model))->process();
    }

    abstract protected function process(): self;

    protected function withSiteDefaults(): self
    {
        $siteDefaults = GetSiteDefaults::handle($this->model)
            ->map(fn ($value) => $value->value());

        return $this->merge($siteDefaults);
    }

    protected function withContentDefaults(): self
    {
        $contentDefaults = GetContentDefaults::handle($this->model)
            ->map(fn ($value) => $value->value());

        return $this->merge($contentDefaults);
    }

    protected function withPageData(): self
    {
        $pageData = GetPageData::handle($this->model)
            ->map(fn ($value) => $value->value());

        return $this->merge($pageData);
    }

    protected function removeSeoPrefix(): self
    {
        $this->data = $this->data->mapWithKeys(fn ($item, $key) => [Str::remove('seo_', $key) => $item]);

        return $this;
    }

    protected function removeSectionFields(): self
    {
        $this->data = $this->data->filter(fn ($item, $key) => ! Str::contains($key, 'section_'));

        return $this;
    }

    protected function sortKeys(): self
    {
        $this->data = $this->data->sortKeys();

        return $this;
    }

    /**
     * Make sure to get the site defaults if there is no value
     * for the overrides keys in the current data.
     */
    protected function ensureOverrides(): self
    {
        // The keys that should be considered for the overrides.
        $overrides = ['noindex', 'nofollow', 'og_image', 'twitter_summary_image', 'twitter_summary_large_image'];

        // The values that should be used as overrides.
        $defaults = GetSiteDefaults::handle($this->model)
            ->only($overrides)
            ->map(fn ($value) => $value->value());

        // The values from the existing data that should be overriden.
        $data = $this->data->only($overrides)->filter();

        // Only merge the defaults overrides if they don't exist in the data.
        $merged = $defaults->diffKeys($data)->merge($data);

        return $this->merge($merged);
    }

    public function values(): Collection
    {
        if (method_exists($this, 'computedValueKeys')) {
            return $this->data->merge($this->computedValues());
        }

        return $this->data;
    }

    public function value(string $key): mixed
    {
        if (method_exists($this, 'computedValueKeys')) {
            return $this->computedValue($key) ?? $this->get($key);
        }

        return $this->get($key);
    }

    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedCascade($this);
    }
}
