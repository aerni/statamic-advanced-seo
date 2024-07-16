<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Concerns\AsAction;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;

class IncludeInSitemap
{
    use AsAction;
    use EvaluatesIndexability;

    public function handle(Entry|Term|Taxonomy $model, ?string $locale = null): bool
    {
        $locale ??= $model->locale();

        return Blink::once("{$model->id()}::{$locale}", fn () => match (true) {
            $model instanceof Entry => $this->includeEntryOrTermInSitemap($model),
            $model instanceof Term => $this->includeEntryOrTermInSitemap($model),
            $model instanceof Taxonomy => $this->includeTaxonomyInSitemap($model, $locale)
        });
    }

    protected function includeEntryOrTermInSitemap(Entry|Term $model): bool
    {
        return ! $this->isExcludedFromSitemap($model, $model->locale)
            && $this->isIndexableEntryOrTerm($model)
            && $model->seo_sitemap_enabled
            && $model->seo_canonical_type == 'current';
    }

    protected function includeTaxonomyInSitemap(Taxonomy $taxonomy, string $locale): bool
    {
        return ! $this->isExcludedFromSitemap($taxonomy, $locale)
            && $this->isIndexableSite($locale);
    }

    protected function isExcludedFromSitemap(Entry|Term|Taxonomy $model, string $locale): bool
    {
        $excluded = Seo::find('site', 'indexing')
            ?->in($locale)
            ?->value('excluded_'.EvaluateModelType::handle($model)) ?? [];

        return in_array(EvaluateModelHandle::handle($model), $excluded);
    }
}
