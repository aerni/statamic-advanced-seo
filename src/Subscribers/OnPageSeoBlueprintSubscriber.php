<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Illuminate\Support\Str;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Traits\GetsEventData;
use Aerni\AdvancedSeo\Traits\GetsFieldsWithDefault;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;
    use GetsFieldsWithDefault;

    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToEntryBlueprint',
        Events\TermBlueprintFound::class => 'addFieldsToTermBlueprint',
        // Events\EntrySaving::class => 'removeDefaultDataFromEntry',
        // Events\TermSaving::class => 'removeDefaultDataFromEntry', // TODO: This event does not currently exist but will be added with an open PR.
        Events\CollectionSaved::class => 'createOrDeleteLocalizations',
        Events\TaxonomySaved::class => 'createOrDeleteLocalizations',
        Events\CollectionDeleted::class => 'deleteDefaults',
        Events\TaxonomyDeleted::class => 'deleteDefaults',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    protected function shouldHandleBlueprintEvents(Event $event): bool
    {
        // Don't add fields in the blueprint builder.
        if (Str::contains(request()->path(), '/blueprints/' . $event->blueprint->handle()) || app()->runningInConsole()) {
            return false;
        }

        // Don't add fields on any custom view.
        if (Str::contains(request()->path(), '/advanced-seo/') || app()->runningInConsole()) {
            return false;
        }

        return true;
    }

    public function addFieldsToEntryBlueprint(Event $event): void
    {
        if (! $this->shouldHandleBlueprintEvents($event)) {
            return;
        }

        /**
         * Add the fields to the entry blueprint in the CP. But only for the current localized entry.
         * This is to prevent that every localization adds fields to the blueprint.
         * If we don't do this check, we can't add the localized content defaults correctly.
         */
        if (Str::containsAll(request()->path(), [$event->entry?->id(), 'cp', 'collections', 'entries'])) {
            $event->blueprint->ensureFieldsInSection($this->blueprint($event)->items(), 'SEO');
        }

        // TODO: Maybe move this out of the BlueprintFound event.
        // Add the data to the entry if we're on the frontend.
        if (request()->route()->getName() === 'statamic.site') {
            $event->blueprint->ensureFieldsInSection($this->blueprint($event)->items(), 'SEO');

            $this->addDefaultDataToEntry($event);
        }

        // TODO: Remove those values (or maybe also just the default key from blueprint) in the entry in EntrySaving event.
        // dd($this->getFieldsWithDefault($blueprint));
    }

    public function addFieldsToTermBlueprint(Event $event): void
    {
        if (! $this->shouldHandleBlueprintEvents($event)) {
            return;
        }

        /**
         * Add the fields to the term blueprint in the CP. But only for the current localized term.
         * This is to prevent that every localization adds fields to the blueprint.
         * If we don't do this check, we can't add the localized content defaults correctly.
         */
        if (Str::containsAll(request()->path(), [$event->term?->slug(), 'cp', 'taxonomies', 'terms'])) {
            $event->blueprint->ensureFieldsInSection($this->blueprint($event)->items(), 'SEO');
        }

        // TODO: Maybe move this out of the BlueprintFound event.
        // Add the data for the frontend.
        if (request()->route()->getName() === 'statamic.site') {
            $event->blueprint->ensureFieldsInSection($this->blueprint($event)->items(), 'SEO');

            $this->addDefaultDataToTerm($event);
        }

        // TODO: Remove those values (or maybe also just the default key from blueprint) in the entry in TermSaving event.
        // dd($this->getFieldsWithDefault($blueprint));
    }

    protected function blueprint(Event $event): OnPageSeoBlueprint
    {
        $data = property_exists($event, 'entry') ? $event->entry : $event->term;

        if (! $data) {
            $data = [
                'type' => Str::before($event->blueprint->namespace(), '.'),
                'handle' => Str::after($event->blueprint->namespace(), '.'),
            ];
        }

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

        // A fancy way to get the current locale because you can't get it from the term.
        $locale = str_contains(request()->path(), config('cp.route', 'cp'))
            ? basename(request()->path())
            : Site::current()->handle();

        $defaults = Seo::find('taxonomies', $event->term->taxonomy()->handle())
            ?->in($locale)
            ?->data();

        if (is_null($defaults)) {
            return;
        }

        // We only want to set the defaults that were not changed on the entry.
        $termData = $event->term->in($locale)->data();
        $defaultsToSet = $defaults->diffKeys($termData);
        $mergedData = $termData->merge($defaultsToSet)->toArray();

        $event->term->in($locale)->merge($mergedData);
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
}
