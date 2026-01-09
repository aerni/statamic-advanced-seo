<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Context\Context;
use Statamic\Events\Event;

trait GetsEventData
{
    protected function getProperty(Event $event): mixed
    {
        $property = collect(['collection', 'entry', 'taxonomy', 'term', 'defaults'])
            ->first(fn ($property) => property_exists($event, $property));

        return $event->$property;
    }

    protected function resolveEventContext(Event $event): Context
    {
        // Fall back to event if no data exists, e.g. when creating an entry/term.
        return Context::from($this->getProperty($event) ?? $event);
    }
}
