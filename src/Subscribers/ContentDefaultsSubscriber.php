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
            Events\CollectionSaved::class => 'createDefaults',
            Events\TaxonomySaved::class => 'createDefaults',
            Events\CollectionDeleted::class => 'deleteDefaults',
            Events\TaxonomyDeleted::class => 'deleteDefaults',
        ];
    }

    public function createDefaults(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->getRepositoryType($event);

        $handle = $property->handle();

        // Abort if Collection or Taxnomy was disabled in the config.
        if (in_array($handle, config("advanced-seo.disabled.{$type}", []))) {
            return;
        }

        Seo::findOrMake($type, $handle)->save();
    }

    public function deleteDefaults(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->getRepositoryType($event);

        Seo::find($type, $property->handle())?->delete();
    }

    protected function getRepositoryType(Event $event): string
    {
        return match (true) {
            (property_exists($event, 'collection')) => 'collections',
            (property_exists($event, 'taxonomy')) => 'taxonomies',
        };
    }
}
