<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\Event;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;

trait GetsEventData
{
    protected function determineRepositoryType(Event $event): string
    {
        return property_exists($event, 'taxonomy')
            ? 'taxonomies'
            : 'collections';
    }

    protected function determineModel(Event $event): string
    {
        return match ($event::class) {
            EntryBlueprintFound::class => 'collections',
            TermBlueprintFound::class => 'taxonomies'
        };
    }

    protected function determineProperty(Event $event): string
    {
        return collect(['collection', 'entry', 'taxonomy', 'term', 'defaults'])
            ->first(function ($property) use ($event) {
                return property_exists($event, $property);
            });
    }

    protected function getProperty(Event $event): mixed
    {
        $property = $this->determineProperty($event);

        return $event->$property;
    }

    protected function getBlueprintFromEvent(Event $event): Blueprint
    {
        return property_exists($event, 'blueprint')
            ? $event->blueprint
            : $this->getProperty($event)->blueprint();
    }

    protected function getDataFromEvent(Event $event): DefaultsData
    {
        $data = $this->getProperty($event);

        // There is no data if we are creating a new entry/term.
        if (! $data) {
            return GetDefaultsData::handle($event);
        }

        // Make sure to get the correct localization of term defaults on the frontend.
        if (Helpers::isFrontendRoute()) {
            $data = $data->in(Site::current()->handle());
        }

        // Fall back to event if no data exists, e.g. non-existent entry localization.
        return GetDefaultsData::handle($data ?? $event);
    }
}
