<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Fields\Value;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Taxonomy;

trait GetsContentDefaults
{
    use GetsLocale;

    public function getContentDefaults(Entry|Term|LocalizedTerm|LaravelCollection $data, string $locale = null): LaravelCollection
    {
        $parent = $this->getContentParent($data);
        $locale = $locale ?? $this->getLocale($data);

        return Blink::once($this->getContentCacheKey($parent, $locale), function () use ($parent, $locale) {
            $defaults = Seo::find($this->getContentType($parent), $this->getContentHandle($parent))
                ?->in($locale)
                ?->toAugmentedArray();

            return collect($defaults)->filter(function ($item) {
                // Only return values that have a corresponding field in the blueprint.
                return $item instanceof Value && $item->raw() !== null;
            });
        });
    }

    protected function getContentCacheKey(Collection|Taxonomy|LaravelCollection $parent, string $locale): string
    {
        return "advanced-seo::{$this->getContentType($parent)}::{$this->getContentHandle($parent)}::{$locale}";
    }

    protected function getContentParent(Entry|Term|LocalizedTerm|LaravelCollection $data): Collection|Taxonomy|LaravelCollection
    {
        if ($data instanceof Entry) {
            return $data->collection();
        }

        if ($data instanceof Term || $data instanceof LocalizedTerm) {
            return $data->taxonomy();
        }

        return $data;
    }

    protected function getContentType(Collection|Taxonomy|LaravelCollection $parent): string
    {
        if ($parent instanceof Collection) {
            return 'collections';
        }

        if ($parent instanceof Taxonomy) {
            return 'taxonomies';
        }

        return $parent->get('type');
    }

    protected function getContentHandle(Collection|Taxonomy|LaravelCollection $parent): string
    {
        if ($parent instanceof Collection) {
            return $parent->handle();
        }

        if ($parent instanceof Taxonomy) {
            return $parent->handle();
        }

        return $parent->get('handle');
    }
}
