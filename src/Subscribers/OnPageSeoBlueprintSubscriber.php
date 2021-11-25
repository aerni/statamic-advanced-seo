<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Aerni\AdvancedSeo\Traits\GetsEventData;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;

    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToBlueprint',
        Events\TermBlueprintFound::class => 'addFieldsToBlueprint',
        // Events\EntrySaving::class => 'removeDefaultDataFromEntry',
        // Events\TermSaving::class => 'removeDefaultDataFromEntry', // TODO: This event does not currently exist but will be added with an open PR.
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
        // Don't add fields in the blueprint builder.
        if (Str::contains(request()->path(), '/blueprints/' . $event->blueprint->handle()) || app()->runningInConsole()) {
            return;
        }

        // Don't add fields on any custom view.
        if (Str::contains(request()->path(), '/advanced-seo/') || app()->runningInConsole()) {
            return;
        }

        $event->blueprint->ensureFieldsInSection($this->blueprint($event)->items(), 'SEO');

        property_exists($event, 'entry')
            ? $this->addDefaultDataToEntry($event)
            : $this->addDefaultDataToTerm($event);
    }

    protected function blueprint(Event $event): OnPageSeoBlueprint
    {
        $data = property_exists($event, 'entry') ? $event->entry : $event->term;

        return OnPageSeoBlueprint::make()->data($data);
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

        $defaults = Seo::find('collections', $event->entry->collection()->handle())
            ?->in($event->entry->locale())
            ?->data();

        if (is_null($defaults)) {
            return;
        }

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

        $defaults = Seo::find('taxonomies', $event->term->taxonomy()->handle())
            ?->in($event->term->locale())
            ?->data();

        if (is_null($defaults)) {
            return;
        }

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
    // public function removeDefaultDataFromEntry(Event $event): void
    // {
    //     $defaults = Seo::find('collections', $event->entry->collection()->handle())
    //         ?->in($event->entry->locale())
    //         ?->data();

    //     if (is_null($defaults)) {
    //         return;
    //     }

    //     $dataWithoutDefaults = $event->entry->data()->filter(function ($value, $key) use ($defaults) {
    //         return $value !== $defaults->get($key);
    //     });

    //     $event->entry->data($dataWithoutDefaults);
    // }

    /**
     * Makes sure that we only save data that is different to the default term.
     * This ensures that the blueprint always loads the latest default data if no other value has been set on the term.
     */
    // public function removeDefaultDataFromTerm(Event $event): void
    // {
    //     $defaults = Seo::find('taxonomies', $event->term->taxonomy()->handle())
    //         ?->in($event->term->locale())
    //         ?->data();

    //     if (is_null($defaults)) {
    //         return;
    //     }

    //     $dataWithoutDefaults = $event->term->data()->filter(function ($value, $key) use ($defaults) {
    //         return $value !== $defaults->get($key);
    //     });

    //     $event->term->data($dataWithoutDefaults);
    // }

    /**
     * Create or delete a localization when the corresponding site
     * was added or removed from a Collection or Taxonomy.
     */
    public function createOrDeleteLocalizations(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->determineRepositoryType($event);

        $handle = $property->handle();
        $sites = $property->sites();

        Seo::findOrMake($type, $handle)->createOrDeleteLocalizations($sites);
    }

    /**
     * Deletes a whole Seo Defaults Set.
     */
    public function deleteDefaults(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->determineRepositoryType($event);

        Seo::find($type, $property->handle())?->delete();
    }

    public function generateSocialImage(Event $event): void
    {
        GenerateSocialImageJob::dispatch($event->entry);
    }
}
