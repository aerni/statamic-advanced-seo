<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Events\CollectionDeleted;
use Statamic\Events\CollectionDeleting;
use Statamic\Events\CollectionTreeEntriesMovedOrRemoved;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntryDeleting;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TaxonomyDeleted;
use Statamic\Events\TaxonomyDeleting;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;
use Statamic\Structures\CollectionTree;

class AutomaticRedirects
{
    public function __construct(
        private AffectedContent $affected,
        private AutomaticEntryRedirects $entries,
        private AutomaticTermRedirects $terms,
        private AutomaticTaxonomyRedirects $taxonomies,
    ) {}

    public function stash(EntrySaving|TermSaving|CollectionTreeEntriesMovedOrRemoved|TaxonomyDeleting|CollectionDeleting|EntryDeleting $event): void
    {
        match (true) {
            $event instanceof EntrySaving => $this->stashEntry($event->entry),
            $event instanceof EntryDeleting => $this->stashDeletion($event->entry),
            $event instanceof TermSaving => $this->terms->stash($event->term),
            $event instanceof TaxonomyDeleting => $this->stashDeletion($event->taxonomy),
            $event instanceof CollectionDeleting => $this->stashDeletion($event->collection),
            $event instanceof CollectionTreeEntriesMovedOrRemoved => $this->stashAffected($this->affected->captureTree($event->moved)),
        };
    }

    public function create(EntrySaved|TermSaved|CollectionTreeSaved $event): void
    {
        match (true) {
            $event instanceof EntrySaved => $this->createEntry($event->entry),
            $event instanceof TermSaved => $this->terms->create($event->term),
            $event instanceof CollectionTreeSaved => $this->createMovedByTree($event->tree),
        };
    }

    public function delete(EntryDeleted|TermDeleted|CollectionDeleted|TaxonomyDeleted $event): void
    {
        match (true) {
            $event instanceof EntryDeleted => $this->deleteEntry($event->entry),
            $event instanceof TermDeleted => $this->terms->delete($event->term),
            $event instanceof CollectionDeleted => $this->deleteAffected($event->collection),
            $event instanceof TaxonomyDeleted => $this->deleteAffected($event->taxonomy),
        };
    }

    private function deleteEntry(Entry $entry): void
    {
        $this->entries->delete($entry);
        $this->deleteAffected($entry);
    }

    private function stashEntry(Entry $entry): void
    {
        $this->entries->stash($entry);
        $changes = $this->affected->captureEntry($entry);
        $this->stashAffected($changes);
        $this->affected->prepareForSave($entry, $changes);
    }

    /** @param array<string, AffectedUrls> $changes */
    private function stashAffected(array $changes): void
    {
        foreach ($changes as $change) {
            $this->entries->stashAffected($change);
            $this->terms->stashAffected($change);
            $this->taxonomies->stashAffected($change);
        }
    }

    private function createEntry(Entry $entry): void
    {
        $this->entries->create($entry);

        if ($change = $this->affected->take($entry->id())) {
            $this->affected->updateForSave($entry, $change);
            $this->createAffected($change);
        }
    }

    private function createMovedByTree(CollectionTree $tree): void
    {
        if ($change = $this->affected->take("{$tree->handle()}::{$tree->locale()}")) {
            $this->affected->updateForTree($tree, $change);
            $this->createAffected($change);
        }
    }

    private function createAffected(AffectedUrls $change): void
    {
        $this->entries->createAffected($change);
        $this->terms->createAffected($change);
        $this->taxonomies->createAffected($change);
    }

    private function stashDeletion(Entry|StatamicCollection|Taxonomy $model): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $key = $this->deletionKey($model);
        $scopes = $this->affected->taxonomiesForDeletion($model);
        $site = $model instanceof Entry ? $model->locale() : null;
        $this->taxonomies->stashDeletion($key, $scopes, $site);

        // Taxonomy deletion dispatches TermDeleted for each of its terms.
        if (! $model instanceof Taxonomy) {
            $this->terms->stashDeletion($key, $scopes, $site);
        }
    }

    private function deleteAffected(Entry|StatamicCollection|Taxonomy $model): void
    {
        $this->affected->forgetDeletedPaths($model);
        $key = $this->deletionKey($model);
        $this->taxonomies->deleteAffected($key);
        $this->terms->deleteAffected($key);
    }

    private function deletionKey(Entry|StatamicCollection|Taxonomy $model): string
    {
        $type = match (true) {
            $model instanceof Entry => 'entry',
            $model instanceof StatamicCollection => 'collection',
            default => 'taxonomy',
        };

        return "{$type}|{$model->id()}";
    }
}
