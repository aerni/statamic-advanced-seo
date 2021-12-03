<?php

namespace Aerni\AdvancedSeo\Traits;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Taxonomy;

trait GetsContentDefaults
{
    use GetsLocale;

    public function getContentDefaults($data): array
    {
        if (! $parent = $this->getContentParent($data)) {
            return [];
        }

        return Blink::once($this->getCacheKey($parent, $data), function () use ($parent, $data) {
            return Seo::find($this->getContentType($parent), $parent->handle())
                ?->in($this->getLocale($data))
                ?->toAugmentedArray();
        });
    }

    protected function getCacheKey($parent, $data): string
    {
        return "advanced-seo::{$this->getContentType($parent)}::{$parent->handle()}::{$data->locale()}";
    }

    protected function getContentParent($data): Collection|Taxonomy|Null
    {
        if ($data instanceof Entry) {
            return $data->collection();
        }

        if ($data instanceof Term || $data instanceof LocalizedTerm) {
            return $data->taxonomy();
        }

        return null;
    }

    protected function getContentType($parent): ?string
    {
        if ($parent instanceof Collection) {
            return 'collections';
        }

        if ($parent instanceof Taxonomy) {
            return 'taxonomies';
        }

        return null;
    }
}
