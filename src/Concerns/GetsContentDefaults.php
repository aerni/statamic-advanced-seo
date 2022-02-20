<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Fields\Value;
use Statamic\Tags\Context;
use Statamic\Facades\Blink;
use Statamic\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Entries\Collection;
use Statamic\Facades\Entry as EntryFacade;
use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Facades\Taxonomy as FacadesTaxonomy;
use Statamic\Stache\Query\TermQueryBuilder;

trait GetsContentDefaults
{
    use GetsLocale;

    public function getContentDefaults(Entry|Term|LocalizedTerm|LaravelCollection|Context $data, string $locale = null): LaravelCollection
    {
        if (! $this->canGetContentDefaults($data)) {
            return collect();
        }

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

    protected function getContentParent(Entry|Term|LocalizedTerm|Context|LaravelCollection $data): Collection|Taxonomy|LaravelCollection
    {
        if ($data instanceof Entry) {
            return $data->collection();
        }

        if ($data instanceof Term || $data instanceof LocalizedTerm) {
            return $data->taxonomy();
        }

        if ($data instanceof Context && $data->get('collection') instanceof Collection) {
            return $data->get('collection');
        }

        if ($data instanceof Context && $data->get('taxonomy') instanceof Taxonomy) {
            return $data->get('taxonomy');
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

    protected function canGetContentDefaults(mixed $data): bool
    {
        // If the context is a taxonomy, we don't have any defaults.
        if ($data instanceof Context && $data->get('terms') instanceof TermQueryBuilder) {
            return false;
        }

        return true;
    }
}
