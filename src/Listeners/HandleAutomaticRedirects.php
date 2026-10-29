<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Events\CollectionDeleted;
use Statamic\Events\CollectionDeleting;
use Statamic\Events\CollectionTreeEntriesMovedOrRemoved;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TaxonomyDeleted;
use Statamic\Events\TaxonomyDeleting;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;

class HandleAutomaticRedirects
{
    public function handleEntrySaving(EntrySaving $event): void
    {
        Redirect::automatic()->stash($event);
    }

    public function handleEntrySaved(EntrySaved $event): void
    {
        Redirect::automatic()->create($event);
    }

    public function handleEntryDeleted(EntryDeleted $event): void
    {
        Redirect::automatic()->delete($event);
    }

    public function handleTermSaving(TermSaving $event): void
    {
        Redirect::automatic()->stash($event);
    }

    public function handleTermSaved(TermSaved $event): void
    {
        Redirect::automatic()->create($event);
    }

    public function handleTermDeleted(TermDeleted $event): void
    {
        Redirect::automatic()->delete($event);
    }

    public function handleCollectionTreeEntriesMovedOrRemoved(CollectionTreeEntriesMovedOrRemoved $event): void
    {
        Redirect::automatic()->stash($event);
    }

    public function handleCollectionTreeSaved(CollectionTreeSaved $event): void
    {
        Redirect::automatic()->create($event);
    }

    public function handleTaxonomyDeleting(TaxonomyDeleting $event): void
    {
        Redirect::automatic()->stash($event);
    }

    public function handleTaxonomyDeleted(TaxonomyDeleted $event): void
    {
        Redirect::automatic()->delete($event);
    }

    public function handleCollectionDeleting(CollectionDeleting $event): void
    {
        Redirect::automatic()->stash($event);
    }

    public function handleCollectionDeleted(CollectionDeleted $event): void
    {
        Redirect::automatic()->delete($event);
    }
}
