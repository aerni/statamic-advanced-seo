<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Statamic;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Illuminate\Support\Str;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\TermBlueprintFound;
use Statamic\Events\EntryBlueprintFound;
use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Facades\Collection as CollectionFacade;

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

        if (! Statamic::isCpRoute()) {
            $data = $data->in(Site::current()->handle());
        }

        return GetDefaultsData::handle($data ?? $event);
    }
}
