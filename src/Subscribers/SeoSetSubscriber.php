<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class SeoSetSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\CollectionDeleted::class => 'deleteSeoSet',
            Events\TaxonomyDeleted::class => 'deleteSeoSet',
        ];
    }

    public function deleteSeoSet(Event $event): void
    {
        $id = match (true) {
            property_exists($event, 'collection') => "collections::{$event->collection->handle()}",
            property_exists($event, 'taxonomy') => "taxonomies::{$event->taxonomy->handle()}",
        };

        Seo::find($id)?->delete();
    }
}
