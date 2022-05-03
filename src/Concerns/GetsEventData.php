<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Events\Event;

trait GetsEventData
{
    protected function getProperty(Event $event): mixed
    {
        $property = collect(['collection', 'entry', 'taxonomy', 'term', 'defaults'])
            ->first(fn ($property) => property_exists($event, $property));

        return $event->$property;
    }

    protected function getDataFromEvent(Event $event): DefaultsData
    {
        // Fall back to event if no data exists, e.g. when creating an entry/term.
        return GetDefaultsData::handle($this->getProperty($event) ?? $event);
    }
}
