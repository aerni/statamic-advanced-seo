<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\Event;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Statamic;
use Statamic\Taxonomies\LocalizedTerm;

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

    protected function getDataFromEvent(Event $event): Entry|Term|LocalizedTerm|Collection
    {
        $data = $this->getProperty($event);

        // Make sure to get the data in the correct locale.
        if ($data && ! Statamic::isCpRoute()) {
            return $data->in(Site::current()->handle());
        }

        // The fallback data is used on "Create Entry" and "Create Term" views so that we can get the content defaults.
        return $data ?? $this->getFallbackData($event);
    }

    // TODO: Make this fallback data its own DTO so that we can typehint it?
    protected function getFallbackData(Event $event): Collection
    {
        $data = collect([
            'type' => Str::before($event->blueprint->namespace(), '.'),
            'handle' => Str::after($event->blueprint->namespace(), '.'),
            'locale' => basename(request()->path()), // TODO: This won't work with subdomain locales.
        ]);

        if ($data['type'] === 'collections') {
            $data->put('sites', CollectionFacade::find($data['handle'])->sites());
        }

        if ($data['type'] === 'taxonomies') {
            $data->put('sites', Taxonomy::find($data['handle'])->sites());
        }

        return $data;
    }
}
