<?php

namespace Aerni\AdvancedSeo\Actions;

use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Taxonomies\LocalizedTerm;

class RemoveSeoValues
{
    /**
     * Remove SEO values from entries or terms.
     * Pass specific field names to remove only those, or omit to remove all seo_* fields.
     */
    public static function handle(mixed $model, ?array $fields = null): void
    {
        match (true) {
            $model instanceof Collection => self::removeValuesFromEntries($model, $fields),
            $model instanceof Taxonomy => self::removeValuesFromTerms($model, $fields),
            default => null,
        };
    }

    protected static function removeValuesFromEntries(Collection $collection, ?array $fields): void
    {
        $collection->queryEntries()->get()
            ->filter(fn (Entry $entry) => self::hasMatchingFields($entry->data(), $fields))
            ->each(function (Entry $entry) use ($fields) {
                $entry->data(self::filterData($entry->data(), $fields))->saveQuietly();
            });
    }

    protected static function removeValuesFromTerms(Taxonomy $taxonomy, ?array $fields): void
    {
        $taxonomy->queryTerms()->get()
            ->filter(fn (LocalizedTerm $localization) => self::hasMatchingFields($localization->data(), $fields))
            ->map(fn (LocalizedTerm $localization) => $localization->term())
            ->unique()
            ->each(function (Term $term) use ($fields) {
                $term->localizations()->each(function (LocalizedTerm $localization) use ($fields) {
                    $localization->data(self::filterData($localization->data(), $fields));
                });

                $term->saveQuietly();
            });
    }

    protected static function hasMatchingFields(LaravelCollection $data, ?array $fields): bool
    {
        return $fields
            ? $data->keys()->intersect($fields)->isNotEmpty()
            : $data->contains(fn ($value, $key) => str($key)->startsWith('seo_'));
    }

    protected static function filterData(LaravelCollection $data, ?array $fields): LaravelCollection
    {
        return $fields
            ? $data->except($fields)
            : $data->reject(fn ($value, $key) => str($key)->startsWith('seo_'));
    }
}
