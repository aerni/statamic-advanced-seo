<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Facades\Request;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\Event;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\Statamic;

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

        // Make sure to get the correct localization of terms on the frontend.
        if (! Statamic::isCpRoute() && ! Helpers::isSocialImagesGeneratorActionRoute()) {
            $data = $data->in(Site::current()->handle());
        }

        // Make sure to get the correct localization for social images routes.
        if (Helpers::isSocialImagesGeneratorActionRoute()) {
            $locale = Site::get(request()->site)?->handle() ?? Site::current()->handle();
            $data = $data->in($locale);
        }

        return GetDefaultsData::handle($data ?? $event);
    }
}
