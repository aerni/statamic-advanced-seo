<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Statamic\Tags\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Sites\Site;
use Statamic\Taxonomies\LocalizedTerm;

trait EvaluatesIndexability
{
    protected function isIndexable(Context|Entry|LocalizedTerm|Site $model): bool
    {
        return match (true) {
            $model instanceof Context => $this->isIndexableContext($model),
            $model instanceof Entry => $this->isIndexableEntryOrTerm($model),
            $model instanceof LocalizedTerm => $this->isIndexableEntryOrTerm($model),
            $model instanceof Site => $this->isIndexableSite($model->handle()),
        };
    }

    protected function isIndexableContext(Context $context): bool
    {
        $model = $this->contextIsEntryOrTerm()
            ? $context->get('id')->augmentable()
            : $context->get('site');

        return $this->isIndexable($model);
    }

    protected function isIndexableEntryOrTerm(Entry|LocalizedTerm $model): bool
    {
        return $this->isIndexableSite($model->locale()) // If the site is not indexable, the model should not be indexed.
            && $model->published() // Unpublished models should not be indexed.
            && $model->url() // Models without a route should not be indexed.
            && ! $model->seo_noindex; // Models with noindex should not be indexed.
    }

    protected function isIndexableSite(string $locale): bool
    {
        if (! $this->crawlingIsEnabled()) {
            return false;
        }

        return ! Seo::find('site', 'indexing')?->in($locale)?->noindex;
    }

    protected function crawlingIsEnabled(): bool
    {
        return in_array(app()->environment(), config('advanced-seo.crawling.environments', []));
    }
}
