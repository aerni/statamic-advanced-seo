<?php

namespace Aerni\AdvancedSeo\Redirects;

use Statamic\Contracts\Entries\Entry;
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

class AutomaticRedirects
{
    public function __construct(
        private AutomaticEntryRedirects $entries,
        private AutomaticTermRedirects $terms,
        private AutomaticTaxonomyRedirects $taxonomies,
    ) {}

    public function stash(EntrySaving|TermSaving|CollectionTreeEntriesMovedOrRemoved|TaxonomyDeleting|CollectionDeleting|EntryDeleting $event): void
    {
        match (true) {
            $event instanceof EntrySaving => $this->entries->stash($event->entry),
            $event instanceof EntryDeleting => $this->taxonomies->stashDeletion($event->entry),
            $event instanceof TermSaving => $this->terms->stash($event->term),
            $event instanceof TaxonomyDeleting => $this->taxonomies->stashDeletion($event->taxonomy),
            $event instanceof CollectionDeleting => $this->taxonomies->stashDeletion($event->collection),
            $event instanceof CollectionTreeEntriesMovedOrRemoved => $this->entries->stashMovedByTree($event->moved),
        };
    }

    public function create(EntrySaved|TermSaved|CollectionTreeSaved $event): void
    {
        match (true) {
            $event instanceof EntrySaved => $this->entries->create($event->entry),
            $event instanceof TermSaved => $this->terms->create($event->term),
            $event instanceof CollectionTreeSaved => $this->entries->createMovedByTree($event->tree),
        };
    }

    public function delete(EntryDeleted|TermDeleted|CollectionDeleted|TaxonomyDeleted $event): void
    {
        match (true) {
            $event instanceof EntryDeleted => $this->deleteEntry($event->entry),
            $event instanceof TermDeleted => $this->terms->delete($event->term),
            $event instanceof CollectionDeleted => $this->taxonomies->delete($event->collection),
            $event instanceof TaxonomyDeleted => $this->taxonomies->delete($event->taxonomy),
        };
    }

    private function deleteEntry(Entry $entry): void
    {
        $this->entries->delete($entry);
        $this->taxonomies->delete($entry);
    }
}
