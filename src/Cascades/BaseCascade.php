<?php

namespace Aerni\AdvancedSeo\Cascades;

use Aerni\AdvancedSeo\Actions\GetContentDefaults;
use Aerni\AdvancedSeo\Actions\GetPageData;
use Aerni\AdvancedSeo\Actions\GetSiteDefaults;
use Aerni\AdvancedSeo\Context\Context as SeoContext;
use Facades\Statamic\Modifiers\CoreModifiers;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
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
    use Conditionable;
    use ContainsData;
    use HasAugmentedInstance;

    public function __construct(protected Context|SeoContext|Entry|Term $model)
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

    protected function withContentConfig(): self
    {
        $config = SeoContext::from($this->model)?->seoSet()?->config();

        if ($config) {
            $this->merge(['twitter_card' => $config->value('twitter_card')]);
        }

        return $this;
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

    protected function sanitizeStrings(): self
    {
        $this->data = $this->data->map(fn ($item, $key) => is_string($item) ? CoreModifiers::sanitize($item, []) : $item);

        return $this;
    }

    protected function sortKeys(): self
    {
        $this->data = $this->data->sortKeys();

        return $this;
    }

    /**
     * Site-level noindex/nofollow are OR overrides: if the site says true, it always wins.
     */
    protected function ensureOverrides(): self
    {
        $overrides = GetSiteDefaults::handle($this->model)
            ->only(['noindex', 'nofollow'])
            ->map(fn ($value) => $value->value())
            ->filter();

        return $this->merge($overrides);
    }

    public function values(): Collection
    {
        if (method_exists($this, 'computedKeys')) {
            return $this->data->merge($this->computedValues());
        }

        return $this->data;
    }

    public function value(string $key): mixed
    {
        if (method_exists($this, 'computedKeys')) {
            return $this->computedValue($key) ?? $this->get($key);
        }

        return $this->get($key);
    }

    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedCascade($this);
    }
}
