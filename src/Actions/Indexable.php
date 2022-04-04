<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;

class Indexable
{
    public static function handle(Entry|Term|Taxonomy $model, string $locale = null): bool
    {
        $locale = $locale ?? $model->locale();

        return Blink::once("{$model->id()}::{$locale}", function () use ($model, $locale) {
            return match (true) {
                ($model instanceof Entry) => self::isIndexableEntry($model),
                ($model instanceof Term) => self::isIndexableTerm($model),
                ($model instanceof Taxonomy) => self::isIndexableTaxonomy($model, $locale),
                'default' => true,
            };
        });
    }

    protected static function isIndexableEntry(Entry $entry): bool
    {
        return self::modelIsIndexable($entry, $entry->locale) && self::contentIsIndexable($entry);
    }

    protected static function isIndexableTerm(Term $term): bool
    {
        return self::modelIsIndexable($term, $term->locale) && self::contentIsIndexable($term);
    }

    protected static function isIndexableTaxonomy(Taxonomy $taxonomy, string $locale): bool
    {
        return self::modelIsIndexable($taxonomy, $locale);
    }

    protected static function modelIsIndexable(Entry|Term|Taxonomy $model, string $locale): bool
    {
        $type = EvaluateModelType::handle($model);
        $handle = EvaluateModelHandle::handle($model);

        $disabled = config("advanced-seo.disabled.{$type}", []);

        // Check if the collection/taxonomy is set to be disabled globally.
        if (in_array($handle, $disabled)) {
            return false;
        }

        $config = Seo::find('site', 'indexing')?->in($locale);

        // If there is no config, the sitemap should be indexable.
        if (is_null($config)) {
            return true;
        }

        // If we have a global noindex, the sitemap shouldn't be indexable.
        if ($config->value('noindex')) {
            return false;
        }

        // Check if the collection/taxonomy is set to be excluded from the sitemap
        $excluded = $config->value("excluded_{$type}") ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($handle, $excluded);
    }

    protected static function contentIsIndexable(Entry|Term $model): bool
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
