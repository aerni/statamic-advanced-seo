<?php

namespace Aerni\AdvancedSeo\Redirects;

use Statamic\Events\CollectionTreeEntriesMovedOrRemoved;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;

class AutomaticRedirects
{
    public function __construct(
        private AutomaticEntryRedirects $entries,
        private AutomaticTermRedirects $terms,
    ) {}

    public function stash(EntrySaving|TermSaving|CollectionTreeEntriesMovedOrRemoved $event): void
    {
        match (true) {
            $event instanceof EntrySaving => $this->entries->stash($event->entry),
            $event instanceof TermSaving => $this->terms->stash($event->term),
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

    public function delete(EntryDeleted|TermDeleted $event): void
    {
        match (true) {
            $event instanceof EntryDeleted => $this->entries->delete($event->entry),
            $event instanceof TermDeleted => $this->terms->delete($event->term),
        };
    }
}
