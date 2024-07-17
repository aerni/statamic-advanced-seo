<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Statamic\Contracts\Entries\Collection;
use Statamic\Taxonomies\Taxonomy;

trait EvaluatesContextType
{
    protected function contextIsEntryOrTerm(): bool
    {
        return $this->model->value('is_entry') || $this->model->value('is_term');
    }

    protected function contextIsTaxonomy(): bool
    {
        return $this->model->get('page') instanceof Taxonomy
            && $this->model->get('page')->collection() === null;
    }

    protected function contextIsCollectionTaxonomy(): bool
    {
        return $this->model->get('page') instanceof Taxonomy
            && $this->model->get('page')->collection() instanceof Collection;
    }
}
