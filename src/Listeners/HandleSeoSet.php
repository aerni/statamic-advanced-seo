<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Events\CollectionDeleted;
use Statamic\Events\CollectionSaved;
use Statamic\Events\TaxonomyDeleted;
use Statamic\Events\TaxonomySaved;

class HandleSeoSet
{
    public function handleCollectionSaved(CollectionSaved $event): void
    {
        $this->saveSeoSet("collections::{$event->collection->handle()}");
    }

    public function handleTaxonomySaved(TaxonomySaved $event): void
    {
        $this->saveSeoSet("taxonomies::{$event->taxonomy->handle()}");
    }

    public function handleCollectionDeleted(CollectionDeleted $event): void
    {
        $this->deleteSeoSet("collections::{$event->collection->handle()}");
    }

    public function handleTaxonomyDeleted(TaxonomyDeleted $event): void
    {
        $this->deleteSeoSet("taxonomies::{$event->taxonomy->handle()}");
    }

    protected function saveSeoSet(string $id): void
    {
        $seoSet = Seo::find($id);

        if (! $seoSet || ! $seoSet->enabled()) {
            return;
        }

        $seoSet->save();
    }

    protected function deleteSeoSet(string $id): void
    {
        Seo::find($id)?->delete();
    }
}
