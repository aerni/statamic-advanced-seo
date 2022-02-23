<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Tags\Context;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Taxonomy;

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
            $type = $this->getContentType($parent);
            $handle = $this->getContentHandle($parent);
            $sites = $this->getContentSites($parent);

            return GetAugmentedDefaults::handle($type, $handle, $locale, $sites);
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

    protected function getContentSites(Collection|Taxonomy|LaravelCollection $parent): LaravelCollection
    {
        if ($parent instanceof Collection) {
            return $parent->sites();
        }

        if ($parent instanceof Taxonomy) {
            return $parent->sites();
        }

        return $parent->get('sites');
    }

    protected function canGetContentDefaults(mixed $data): bool
    {
        // If the context is a taxonomy, we don't have any defaults.
        if ($data instanceof Context && $data->get('terms') instanceof TermQueryBuilder) {
            return false;
        }

        // We can't get any defaults if we're on an error page.
        if ($data instanceof Context && Str::contains($data->get('current_template'), 'errors')) {
            return false;
        }

        return true;
    }
}
