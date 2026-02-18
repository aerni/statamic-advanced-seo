<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Taxonomies\LocalizedTerm;

class RemoveSeoValues
{
    public static function handle(mixed $model): void
    {
        match (true) {
            $model instanceof Collection => self::removeValuesFromEntries($model),
            $model instanceof Taxonomy => self::removeValuesFromTerms($model),
            default => null,
        };
    }

    protected static function removeValuesFromEntries(Collection $collection): void
    {
        $collection->queryEntries()->get()
            ->filter(fn (Entry $entry) => $entry->data()->contains(fn ($value, $key) => str($key)->startsWith('seo_')))
            ->each(function (Entry $entry) {
                $dataWithoutSeo = $entry->data()->filter(fn ($value, $key) => ! str($key)->startsWith('seo_'));
                $entry->data($dataWithoutSeo)->saveQuietly();
            });
    }

    protected static function removeValuesFromTerms(Taxonomy $taxonomy): void
    {
        $taxonomy->queryTerms()->get()
            ->filter(fn (LocalizedTerm $localization) => $localization->data()->contains(fn ($value, $key) => str($key)->startsWith('seo_')))
            ->map(fn (LocalizedTerm $localization) => $localization->term())
            ->unique()
            ->each(function (Term $term) {
                $term->localizations()->each(function (LocalizedTerm $localization) {
                    $dataWithoutSeo = $localization->data()->filter(fn ($value, $key) => ! str($key)->startsWith('seo_'));
                    $localization->data($dataWithoutSeo);
                });

                $term->saveQuietly();
            });
    }
}
