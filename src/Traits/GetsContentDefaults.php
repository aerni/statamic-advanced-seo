<?php

namespace Aerni\AdvancedSeo\Traits;

use Statamic\Fields\Value;
use Illuminate\Support\Arr;
use Statamic\Facades\Blink;
use Statamic\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Entries\Collection;
use Illuminate\Support\Collection as LaravelCollection;

trait GetsContentDefaults
{
    use GetsLocale;

    public function getContentDefaults($data): LaravelCollection
    {
        $parent = $this->getContentParent($data);

        return Blink::once($this->getContentCacheKey($parent, $data), function () use ($parent, $data) {
            $defaults = Seo::find($this->getContentType($parent), $this->getContentHandle($parent))
                ?->in($this->getLocale($data))
                ?->toAugmentedArray();

            return collect($defaults)->filter(function ($item) {
                // Only return values that have a corresponding field in the blueprint.
                return $item instanceof Value;
            });
        });
    }

    protected function getContentCacheKey($parent, $data): string
    {
        return "advanced-seo::{$this->getContentType($parent)}::{$this->getContentHandle($parent)}::{$this->getLocale($data)}";
    }

    protected function getContentParent(Entry|Term|LocalizedTerm|array $data): Collection|Taxonomy|array
    {
        if ($data instanceof Entry) {
            return $data->collection();
        }

        if ($data instanceof Term || $data instanceof LocalizedTerm) {
            return $data->taxonomy();
        }

        return $data;
    }

    protected function getContentType(Collection|Taxonomy|array $parent): string
    {
        if ($parent instanceof Collection) {
            return 'collections';
        }

        if ($parent instanceof Taxonomy) {
            return 'taxonomies';
        }

        return Arr::get($parent, 'type');
    }

    protected function getContentHandle(Collection|Taxonomy|array $parent): string
    {
        if ($parent instanceof Collection) {
            return $parent->handle();
        }

        if ($parent instanceof Taxonomy) {
            return $parent->handle();
        }

        return Arr::get($parent, 'handle');
    }
}
