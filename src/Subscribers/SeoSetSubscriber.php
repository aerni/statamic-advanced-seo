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
            Events\CollectionSaved::class => 'saveSeoSet',
            Events\CollectionDeleted::class => 'deleteSeoSet',
            Events\TaxonomySaved::class => 'saveSeoSet',
            Events\TaxonomyDeleted::class => 'deleteSeoSet',
        ];
    }

    public function saveSeoSet(Event $event): void
    {
        $seoSet = Seo::find($this->getId($event));

        if (! $seoSet || ! $seoSet->enabled()) {
            return;
        }

        $seoSet->save();
    }

    public function deleteSeoSet(Event $event): void
    {
        Seo::find($this->getId($event))?->delete();
    }

    protected function getId(Event $event): string
    {
        return match (true) {
            property_exists($event, 'collection') => "collections::{$event->collection->handle()}",
            property_exists($event, 'taxonomy') => "taxonomies::{$event->taxonomy->handle()}",
        };
    }
}
