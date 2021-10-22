<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Aerni\AdvancedSeo\Repositories\SeoDefaultsRepository;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;

class OnPageSeoBlueprintSubscriber
{
    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToBlueprint',
        Events\TermBlueprintFound::class => 'addFieldsToBlueprint',
        Events\EntrySaving::class => 'removeDefaultDataFromEntry',
        // TODO: This event does not currently exist but will be added with an open PR.
        // Events\TermSaving::class => 'removeDefaultDataFromEntry',
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

        $event->blueprint->ensureFieldsInSection(OnPageSeoBlueprint::make()->items(), 'SEO');

        property_exists($event, 'entry')
            ? $this->addDefaultDataToEntry($event)
            : $this->addDefaultDataToTerm($event);
    }

    /**
     * Adds the content defaults to the entry.
     * It only adds values if they have not already been set on the entry.
     */
    protected function addDefaultDataToEntry(Event $event): void
    {
        if (! $event->entry) {
            return;
        }

        $collection = $event->entry->collection();
        $defaults = (new SeoDefaultsRepository('collections', $collection->handle(), $collection->sites()))->set()->in($event->entry->locale())->data();

        // We only want to set the defaults that were not changed on the entry.
        $entryData = $event->entry->data();
        $defaultsToSet = $defaults->diffKeys($entryData);
        $mergedData = $entryData->merge($defaultsToSet)->toArray();

        $event->entry->data($mergedData);
    }

    /**
     * Adds the content defaults to the term.
     * It only adds values if they have not already been set on the term.
     */
    protected function addDefaultDataToTerm(Event $event): void
    {
        if (! $event->term) {
            return;
        }

        $taxonomy = $event->term->taxonomy();
        $defaults = (new SeoDefaultsRepository('taxonomies', $taxonomy->handle(), $taxonomy->sites()))->set()->in($event->term->locale())->data();

        // We only want to set the defaults that were not changed on the entry.
        $termData = $event->term->data();
        $defaultsToSet = $defaults->diffKeys($termData);
        $mergedData = $termData->merge($defaultsToSet)->toArray();

        $event->term->data($mergedData);
    }

    /**
     * Makes sure that we only save data that is different to the default data.
     * This ensures that the blueprint always loads the latest default data if no other value has been set on the entry.
     */
    public function removeDefaultDataFromEntry(Event $event): void
    {
        $collection = $event->entry->collection();
        $defaults = (new SeoDefaultsRepository('collections', $collection->handle(), $collection->sites()))->set()->in($event->entry->locale())->data();

        $dataWithoutDefaults = $event->entry->data()->filter(function ($value, $key) use ($defaults) {
            return $value !== $defaults->get($key);
        });

        $event->entry->data($dataWithoutDefaults);
    }

    /**
     * Makes sure that we only save data that is different to the default term.
     * This ensures that the blueprint always loads the latest default data if no other value has been set on the term.
     */
    public function removeDefaultDataFromTerm(Event $event): void
    {
        $taxonomy = $event->term->taxonomy();
        $defaults = (new SeoDefaultsRepository('taxonomies', $taxonomy->handle(), $taxonomy->sites()))->set()->in($event->term->locale())->data();

        $dataWithoutDefaults = $event->term->data()->filter(function ($value, $key) use ($defaults) {
            return $value !== $defaults->get($key);
        });

        $event->term->data($dataWithoutDefaults);
    }

    /**
     * Create or delete a localization when the corresponding site
     * was added or removed from a Collection or Taxonomy.
     */
    public function createOrDeleteLocalizations(Event $event): void
    {
        $property = $this->determineProperty($event);
        $type = $this->determineRepositoryType($event);

        $handle = $property->handle();
        $sites = $property->sites();

        (new SeoDefaultsRepository($type, $handle, $sites))->createOrDeleteLocalizations($sites);
    }

    /**
     * Deletes a whole Seo Defaults Set.
     */
    public function deleteDefaults(Event $event): void
    {
        $property = $this->determineProperty($event);
        $type = $this->determineRepositoryType($event);

        $handle = $property->handle();
        $sites = $property->sites();

        (new SeoDefaultsRepository($type, $handle, $sites))->delete();
    }

    public function generateSocialImage(Event $event): void
    {
        GenerateSocialImageJob::dispatch($event->entry);
    }

    protected function determineRepositoryType(Event $event): mixed
    {
        return property_exists($event, 'taxonomy')
            ? 'taxonomies'
            : 'collections';
    }

    protected function determineProperty(Event $event): mixed
    {
        return property_exists($event, 'taxonomy')
            ? $event->taxonomy
            : $event->collection;
    }
}
