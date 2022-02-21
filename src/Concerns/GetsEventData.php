<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Statamic;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Illuminate\Support\Str;
use Statamic\Fields\Blueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\TermBlueprintFound;
use Statamic\Events\EntryBlueprintFound;

trait GetsEventData
{
    use ShouldHandleRoute;

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

        // This data is used on "Create Entry" and "Create Term" views so that we can get the content defaults.
        $fallbackData = collect([
            'type' => Str::before($event->blueprint->namespace(), '.'),
            'handle' => Str::after($event->blueprint->namespace(), '.'),
            'locale' => basename(request()->path()), // TODO: This won't work with subdomain locales.
        ]);

        return $data ?? $fallbackData;
    }
}
