<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Statamic\Taxonomies\Taxonomy;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Entries\Collection;

trait EvaluatesContextType
{
    protected function contextIsEntryOrTerm(): bool
    {
        return $this->model->value('is_entry')
            || ($this->model->value('is_term') && ! $this->contextIsCollectionTerm());
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

    protected function contextIsCollectionTerm(): bool
    {
        return $this->model->get('page') instanceof LocalizedTerm
            && $this->model->get('page')->collection() instanceof Collection;
    }
}
