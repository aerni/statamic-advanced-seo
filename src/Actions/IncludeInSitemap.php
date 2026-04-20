<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Concerns\AsAction;
use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Features\Sitemap;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;

class IncludeInSitemap
{
    use AsAction;
    use EvaluatesIndexability;

    protected Context $context;

    public function handle(Entry|Term|Collection|Taxonomy $model, ?string $site = null): bool
    {
        if ($this->siteIsRequired($model) && $site === null) {
            throw new \Exception('A site is required if the model is a Collection or Taxonomy.');
        }

        $this->context = Context::from($model);

        if ($site) {
            $this->context->site = $site;
        }

        return Blink::once("include-in-sitemap-{$this->context->id()}::{$model->id()}", fn () => match (true) {
            $model instanceof Entry => $this->includeEntryOrTermInSitemap($model),
            $model instanceof Term => $this->includeEntryOrTermInSitemap($model),
            $model instanceof Collection => $this->includeCollectionOrTaxonomyInSitemap($model),
            $model instanceof Taxonomy => $this->includeCollectionOrTaxonomyInSitemap($model)
        });
    }

    protected function includeEntryOrTermInSitemap(Entry|Term $model): bool
    {
        return Sitemap::enabled($this->context)
            && $this->isIndexableEntryOrTerm($model)
            && $model->seo_sitemap_enabled
            && $model->seo_canonical_type == 'current';
    }

    protected function includeCollectionOrTaxonomyInSitemap(Collection|Taxonomy $model): bool
    {
        // TODO: Currently, taxonomies don't have a routes method. But they should once PR https://github.com/statamic/cms/pull/8627 is merged.
        $hasRouteForSite = $model instanceof Collection
            ? $model->routes()->filter()->has($this->context->site)
            : true;

        return Sitemap::enabled($this->context)
            && $this->isIndexableSite($this->context->site)
            && $hasRouteForSite;
    }

    protected function siteIsRequired($model): bool
    {
        return $model instanceof Taxonomy || $model instanceof Collection;
    }
}
