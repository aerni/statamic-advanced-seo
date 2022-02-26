<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
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

    public function getContentDefaults(mixed $data): LaravelCollection
    {
        if (! $parent = $this->getContentParent($data)) {
            return collect();
        }

        // TODO: Can we just make this an array and pass it to GetAugmentedDefaults?
        $type = $this->getContentType($parent);
        $handle = $this->getContentHandle($parent);
        $locale = $this->getLocale($data);
        $sites = $this->getContentSites($parent);

        return Blink::once($this->getContentCacheKey($parent, $locale), function () use ($type, $handle, $locale, $sites) {
            return GetAugmentedDefaults::handle($type, $handle, $locale, $sites);
        });
    }

    protected function getContentParent(mixed $data): mixed
    {
        return match (true) {
            ($data instanceof Entry) => $data->collection(),
            ($data instanceof Term) => $data->taxonomy(),
            ($data instanceof LocalizedTerm) => $data->taxonomy(),
            ($data instanceof Context && $data->get('collection') instanceof Collection) => $data->get('collection'),
            ($data instanceof Context && $data->get('taxonomy') instanceof Taxonomy) => $data->get('taxonomy'),
            ($data instanceof Context && $data->get('terms') instanceof TermQueryBuilder) => null, // We can't get any defaults for taxonomy index pages.
            ($data instanceof Context && Str::contains($data->get('current_template'), 'errors')) => null, // We can't get any defaults for error pages.
            default => $data, // TODO: The default case is the fallback data in GetsEventData and the blueprint method in the SeoDefaultSet. Make this its own class.
        };
    }

    protected function getContentType(mixed $parent): string
    {
        return match (true) {
            ($parent instanceof Collection) => 'collections',
            ($parent instanceof Taxonomy) => 'taxonomies',
            ($parent instanceof LaravelCollection) => $parent->get('type'),
            default => throw new \Exception('No type could be found for the provided parent.')
        };
    }

    protected function getContentHandle(mixed $parent): string
    {
        return match (true) {
            ($parent instanceof Collection) => $parent->handle(),
            ($parent instanceof Taxonomy) => $parent->handle(),
            ($parent instanceof LaravelCollection) => $parent->get('handle'),
            default => throw new \Exception('No handle could be found for the provided parent.')
        };
    }

    protected function getContentSites(mixed $parent): LaravelCollection
    {
        return match (true) {
            ($parent instanceof Collection) => $parent->sites(),
            ($parent instanceof Taxonomy) => $parent->sites(),
            ($parent instanceof LaravelCollection) => $parent->get('sites'),
            default => throw new \Exception('No sites could be found for the provided parent.')
        };
    }

    protected function getContentCacheKey(mixed $parent, string $locale): string
    {
        return "advanced-seo::{$this->getContentType($parent)}::{$this->getContentHandle($parent)}::{$locale}";
    }
}
