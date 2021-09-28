<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Statamic\Events;
use Statamic\Events\Event;
use Illuminate\Support\Str;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;
use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;

class OnPageSeoBlueprintSubscriber
{
    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToBlueprint',
        Events\TermBlueprintFound::class => 'addFieldsToBlueprint',
        Events\CollectionSaved::class => 'createOrDeleteLocalizations',
        Events\TaxonomySaved::class => 'createOrDeleteLocalizations',
        Events\CollectionDeleted::class => 'deleteDefaults',
        Events\TaxonomyDeleted::class => 'deleteDefaults',
        Events\EntrySaved::class => 'generateSocialImage',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function addFieldsToBlueprint(Event $event): void
    {
        if (Str::contains(request()->path(), '/blueprints/' . $event->blueprint->handle()) || app()->runningInConsole()) {
            return;
        }

        $contents = array_merge_recursive(
            $event->blueprint->contents(),
            OnPageSeoBlueprint::make()->get()->contents()
        );

        $event->blueprint->setContents($contents);
    }

    public function createOrDeleteLocalizations(Event $event): void
    {
        $property = $this->determineProperty($event);
        $repository = $this->determineRepository($event);

        $handle = $property->handle();
        $sites = $property->sites();

        (new $repository($handle, $sites))->createOrDeleteLocalizations($sites);
    }

    public function deleteDefaults(Event $event): void
    {
        $repository = $this->determineRepository($event);

        $repository->delete();
    }

    public function generateSocialImage(Event $event): void
    {
        GenerateSocialImageJob::dispatch($event->entry);
    }

    protected function determineRepository(Event $event): mixed
    {
        return property_exists($event, 'taxonomy')
            ? TaxonomyDefaultsRepository::class
            : CollectionDefaultsRepository::class;
    }

    protected function determineProperty(Event $event): mixed
    {
        return property_exists($event, 'taxonomy')
            ? $event->taxonomy
            : $event->collection;
    }
}
