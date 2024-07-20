<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Contracts\Entries\Collection;
use Statamic\Tags\Context;
use Statamic\Taxonomies\Taxonomy;

trait EvaluatesContextType
{
    protected function contextIsEntryOrTerm(Context $context): bool
    {
        return $context->value('is_entry') || $context->value('is_term');
    }

    protected function contextIsTaxonomy(Context $context): bool
    {
        return $context->get('page') instanceof Taxonomy
            && $context->get('page')->collection() === null;
    }

    protected function contextIsCollectionTaxonomy(Context $context): bool
    {
        return $context->get('page') instanceof Taxonomy
            && $context->get('page')->collection() instanceof Collection;
    }
}
