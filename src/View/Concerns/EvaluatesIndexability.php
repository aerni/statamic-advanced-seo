<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;

trait EvaluatesIndexability
{
    protected function isIndexable(Entry|LocalizedTerm $model): bool
    {
        return $this->isIndexableSite($model->locale()) // If the site is not indexable, the model should not be indexed.
            && $model->published() // Unpublished models should not be indexed.
            && $model->url() // Models without a route should not be indexed.
            && ! $model->seo_noindex; // Models with noindex should not be indexed.
    }

    protected function isIndexableSite(string $locale): bool
    {
        // If crawling is disabled, the site should not be indexed.
        if (! in_array(app()->environment(), config('advanced-seo.crawling.environments', []))) {
            return false;
        }

        $siteNoindex = Seo::find('site', 'indexing')
            ?->in($locale)
            ?->noindex;

        // If noindex is enabled, the site is should not be indexed.
        return ! $siteNoindex;
    }
}
