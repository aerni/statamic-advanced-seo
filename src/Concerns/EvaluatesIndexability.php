<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Sites\Site;
use Statamic\Tags\Context;
use Statamic\Taxonomies\LocalizedTerm;

trait EvaluatesIndexability
{
    use EvaluatesContextType;

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
        $model = $this->contextIsEntryOrTerm($context)
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
        // TODO: Does this augmented value merge the noindex from the site defaults?
        // If so, we might not need to check for isIndexableSite() here, as noindex would be checked double.
    }

    protected function isIndexableSite(string $site): bool
    {
        return $this->crawlingIsEnabled()
            && ! Seo::find('site', 'indexing')?->in($site)?->noindex;
    }

    protected function crawlingIsEnabled(): bool
    {
        return in_array(app()->environment(), config('advanced-seo.crawling.environments', []));
    }
}
