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

    protected function getContentType(mixed $parent): string
    {
        return match ($parent) {
            ($parent instanceof Collection) => 'collections',
            ($parent instanceof Taxonomy) => 'taxonomies',
            ($parent instanceof LaravelCollection) => $parent->get('type'),
            default => throw new \Exception('No type could be found for the provided parent.')
        };
    }

    protected function getContentHandle(mixed $parent): string
    {
        return match ($parent) {
            ($parent instanceof Collection) => $parent->handle(),
            ($parent instanceof Taxonomy) => $parent->handle(),
            ($parent instanceof LaravelCollection) => $parent->get('handle'),
            default => throw new \Exception('No handle could be found for the provided parent.')
        };
    }

    protected function getContentSites(mixed $parent): LaravelCollection
    {
        return match ($parent) {
            ($parent instanceof Collection) => $parent->sites(),
            ($parent instanceof Taxonomy) => $parent->sites(),
            ($parent instanceof LaravelCollection) => $parent->get('sites'),
            default => throw new \Exception('No sites could be found for the provided parent.')
        };
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
