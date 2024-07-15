<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\Concerns\AsAction;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\View\Concerns\EvaluatesIndexability;

// TODO: Merge this action with the EvaluatesIndexability trait as they are very similar.
class IncludeInSitemap
{
    use AsAction;
    use EvaluatesIndexability;

    public function handle(Entry|Term|Taxonomy $model, ?string $locale = null): bool
    {
        $locale ??= $model->locale();

        return Blink::once("{$model->id()}::{$locale}", function () use ($model, $locale) {
            return match (true) {
                ($model instanceof Entry) => $this->isIndexableEntry($model),
                ($model instanceof Term) => $this->isIndexableTerm($model),
                ($model instanceof Taxonomy) => $this->isIndexableTaxonomy($model, $locale),
                'default' => true,
            };
        });
    }

    protected function isIndexableEntry(Entry $entry): bool
    {
        return $this->modelIsIndexable($entry, $entry->locale) && $this->contentIsIndexable($entry);
    }

    protected function isIndexableTerm(Term $term): bool
    {
        return $this->modelIsIndexable($term, $term->locale) && $this->contentIsIndexable($term);
    }

    protected function isIndexableTaxonomy(Taxonomy $taxonomy, string $locale): bool
    {
        return $this->modelIsIndexable($taxonomy, $locale);
    }

    protected function modelIsIndexable(Entry|Term|Taxonomy $model, string $locale): bool
    {
        if (! $this->isIndexableSite($locale)) {
            return false;
        }

        $type = EvaluateModelType::handle($model);
        $handle = EvaluateModelHandle::handle($model);

        // Check if the collection/taxonomy is set to be excluded from the sitemap
        $excluded = $config->value("excluded_{$type}") ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($handle, $excluded);
    }

    protected function contentIsIndexable(Entry|Term $model): bool
    {
        // Don't index models that are not published.
        if ($model->published() === false) {
            return false;
        }

        // Don't index models that have no URI.
        if ($model->uri() === null) {
            return false;
        }

        // If the sitemap is disabled, we shouldn't index the model.
        if (! $model->seo_sitemap_enabled) {
            return false;
        }

        // Check if noindex is enabled.
        if ($model->seo_noindex) {
            return false;
        }

        // If the canonical type isn't current, we shouldn't index the model.
        if ($model->seo_canonical_type != 'current') {
            return false;
        }

        return true;
    }
}
