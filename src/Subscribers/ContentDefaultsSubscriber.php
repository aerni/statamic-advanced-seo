<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class ContentDefaultsSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\CollectionSaved::class => 'saveSeoSet',
            Events\TaxonomySaved::class => 'saveSeoSet',
            Events\CollectionDeleted::class => 'deleteSeoSet',
            Events\TaxonomyDeleted::class => 'deleteSeoSet',
        ];
    }

    public function saveSeoSet(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->getRepositoryType($event);

        Seo::find("{$type}::{$property->handle()}")?->save();
    }

    public function deleteSeoSet(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->getRepositoryType($event);

        Seo::find("{$type}::{$property->handle()}")?->delete();
    }

    protected function getRepositoryType(Event $event): string
    {
        return match (true) {
            (property_exists($event, 'collection')) => 'collections',
            (property_exists($event, 'taxonomy')) => 'taxonomies',
        };
    }
}
