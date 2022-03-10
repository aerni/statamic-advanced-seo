<?php

namespace Aerni\AdvancedSeo\Migrators;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Term as TermFacade;

abstract class BaseMigrator
{
    public static function run(): void
    {
        (new static())->process();
    }

    protected function process(): void
    {
        EntryFacade::all()->each(fn ($entry) => $this->updateEntry($entry));
        TermFacade::all()->each(fn ($term) => $this->updateTerm($term));
    }

    protected function updateEntry(Entry $entry): void
    {
        $updated = $this->update($entry->data());

        $entry->data($updated)->saveQuietly();
    }

    protected function updateTerm(Term $term): void
    {
        $term->taxonomy()->sites()->each(function ($site) use ($term) {
            $updated = $this->update($term->in($site)->data());

            $term->in($site)->data($updated);
        });

        $term->save();
    }

    /**
     * Update the data from the old to the new format.
     */
    protected function update(Collection $data): Collection
    {
        $nonSeoFields = $data->diffKeys($this->fields());
        $seoFieldsToMigrate = $data->intersectByKeys($this->fields()->filter());

        $migratedSeoFields = $seoFieldsToMigrate->mapWithKeys(fn ($value, $key) => [$this->fields()->get($key) => $value]);

        return $nonSeoFields
            ->merge($migratedSeoFields)
            ->pipe(fn ($data) => $this->transform($data));
    }

    /**
     * We are piping the data through this method to transform
     * any values that need to be different and also add new ones.
     */
    protected function transform(Collection $data): Collection
    {
        return $data;
    }

    /**
     * A map of old and new keys. Old keys with a new key of `null` will be removed.
     */
    abstract protected function fields(): Collection;
}
