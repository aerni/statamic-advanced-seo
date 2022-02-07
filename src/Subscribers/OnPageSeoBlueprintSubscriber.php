<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Concerns\GetsContentDefaults;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Concerns\GetsFieldDefaults;
use Aerni\AdvancedSeo\Concerns\ShouldHandleRoute;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;
    use GetsFieldDefaults;
    use GetsContentDefaults;
    use ShouldHandleRoute;

    protected array $events = [
        Events\EntryBlueprintFound::class => 'handleEntryBlueprintFound',
        Events\EntrySaved::class => 'handleEntrySaved',
        Events\TermBlueprintFound::class => 'handleTermBlueprintFound',
        Events\TermSaved::class => 'handleTermSaved',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    protected function shouldHandleEntryBlueprintEvents(Event $event): bool
    {
        $collection = Str::after($event->blueprint->namespace(), '.');

        // Don't add fields if the collection is excluded in the config.
        if (in_array($collection, config('advanced-seo.excluded_collections', []))) {
            return false;
        }

        // Don't add any fields in the blueprint builder.
        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'blueprints']) || app()->runningInConsole()) {
            return false;
        }

        // Don't add any fields on custom views.
        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'advanced-seo']) || app()->runningInConsole()) {
            return false;
        }

        return true;
    }

    protected function shouldHandleTermBlueprintEvents(Event $event): bool
    {
        $taxonomy = Str::after($event->blueprint->namespace(), '.');

        // Don't add fields if the taxonomy is excluded in the config.
        if (in_array($taxonomy, config('advanced-seo.excluded_taxonomies', []))) {
            return false;
        }

        // Don't add any fields in the blueprint builder.
        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'blueprints']) || app()->runningInConsole()) {
            return false;
        }

        // Don't add any fields on custom views.
        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'advanced-seo']) || app()->runningInConsole()) {
            return false;
        }

        return true;
    }

    public function handleEntryBlueprintFound(Event $event): void
    {
        if ($this->shouldHandleEntryBlueprintEvents($event)) {
            $this->addEntryDefaultsInCp($event);
            $this->addEntryDefaultsToCascade($event);
        }
    }

    public function handleEntrySaved(Event $event): void
    {
        /**
         * Only save the defaults if we're localizing an entry.
         * We need this because an entry's origin data takes precedence over the field defaults in the blueprint.
         * But we want to make sure to set the blueprint defaults when first creating the entry.
         */
        if (Str::contains(request()->path(), 'localize')) {
            $this->saveEntryDefaults($event);
        };
    }

    protected function addEntryDefaultsInCp(Event $event): void
    {
        $isEditingOrLocalizing = $event->entry?->id();
        $isCreating = Arr::get(Site::all()->map->handle(), basename(request()->path()));

        /**
         * We only want to add fields to the blueprint of the current localization.
         * This is to prevent that every localization adds fields to the blueprint.
         * The localized field placeholders and defaults won't be added correctly if we don't do this.
         */
        $status = $isEditingOrLocalizing ?? $isCreating;

        // TODO: BETTER LOCALIZING DEFAULTS: When we're first localizing an entry we are getting its blueprint with $event->entry->blueprint().
        // That method triggers the EntryBlueprintFound event, which in turn triggers this method here.
        // The event's request is includes the entry's origin, which is why the returned blueprint includes the default data of it.
        // We should find a way to get the localizing defaults instead.

        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'collections', 'entries', $status])) {
            $this->extendBlueprint($event);
        }
    }

    protected function addEntryDefaultsToCascade(Event $event): void
    {
        // We only want to add data if we're on a Statamic frontend route.
        if (! $this->isFrontendRoute()) {
            return;
        }

        // Extend the blueprint so that we can add data.
        $this->extendBlueprint($event);

        // Get the entry's defaults.
        $defaults = collect($this->getContentDefaults($event->entry))->map->raw();

        // Get the entry's values (entry + origin). Use a fresh copy for each event so that we don't work with previously added data.
        $data = $event->entry->fresh()->values()->filter(function ($value) {
            // If a field is desynced and empty, we want to get its defaults value instead of null.
            return $value !== null;
        });

        // We only want to set a default value if its key doesn't exist on the entry.
        $defaultsToSet = $defaults->diffKeys($data);

        $event->entry->merge($defaultsToSet);
    }

    protected function saveEntryDefaults(Event $event): void
    {
        // TODO: BETTER LOCALIZING DEFAULTS: We could probably ommit the 'true' parameter if we made
        // defaults localization on extending blueprint work.

        // Get the entry's blueprint defaults.
        $defaults = $this->getFieldDefaults($event->entry->blueprint(), true);

        // Get the data from the origin.
        $data = $event->entry->origin()->data();

        // Retain field sync status by removing any defaults that already exist on the origin.
        $defaultsToSet = $defaults->diffAssoc($data);

        $event->entry->merge($defaultsToSet)->saveQuietly();
    }

    public function handleTermBlueprintFound(Event $event): void
    {
        if ($this->shouldHandleTermBlueprintEvents($event)) {
            $this->addTermDefaultsInCp($event);
            $this->addTermDefaultsToCascade($event);
        }
    }

    public function handleTermSaved(Event $event): void
    {
        /**
         * Only save the defaults if we're creating a term.
         * We need this because an term's origin data takes precedence over the field defaults in the blueprint.
         * But we want to make sure to set the blueprint defaults when first creating a localized term.
         */
        if (Str::contains(url()->previous(), 'create')) {
            $this->saveTermDefaults($event);
        }
    }

    protected function addTermDefaultsInCp(Event $event): void
    {
        $isEditingOrLocalizing = $event->term?->slug();
        $isCreating = Arr::get(Site::all()->map->handle(), basename(request()->path()));

        /**
         * We only want to add fields to the blueprint of the current localization.
         * This is to prevent that every localization adds fields to the blueprint.
         * The localized field placeholders and defaults won't be added correctly if we don't do this.
         */
        $status = $isEditingOrLocalizing ?? $isCreating;

        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'taxonomies', 'terms', $status])) {
            $this->extendBlueprint($event);
        }
    }

    protected function addTermDefaultsToCascade(Event $event): void
    {
        // We only want to add data if we're on a Statamic frontend route.
        if (! $this->isFrontendRoute()) {
            return;
        }

        // Extend the blueprint so that we can add data.
        $this->extendBlueprint($event);

        // Get the term's defaults.
        $defaults = collect($this->getContentDefaults($event->term))->map->raw();

        // Get the term's locale.
        $locale = $this->getLocale($event->term);

        // Get the term's values (localization + origin).
        $data = $event->term->in($locale)->values()->filter(function ($value) {
            // If a field is desynced and empty, we want to get its defaults value instead of null.
            return $value !== null;
        });

        // We only want to set a default value if its key doesn't exist on the term.
        $defaultsToSet = $defaults->diffKeys($data);

        $event->term->in($locale)->merge($defaultsToSet);
    }

    /**
     * TODO: Creating a term on a locale other than the origin will leave you with unsynced fields on the locale.
     * We would need some fancy workaround to make this work. The underlying issue is the core Statamic taxonomy concept.
     * Probably a good idea to wait until some multi-site fixes get implemented. Like being able to set the origin.
     */
    protected function saveTermDefaults(Event $event): void
    {
        // Get the term's blueprint.
        $blueprint = $event->term->blueprint();

        // Get the defaults for each localization. If nothing gets returned, there is nothing to save.
        $localizations = $event->term->localizations()->map(function ($localization) use ($blueprint) {
            // TODO: BETTER LOCALIZING DEFAULTS: We could probably ommit the 'true' and 'locale' parameter if we made
            // defaults localization on extending blueprint work.

            // Get the localized term's blueprint defaults.
            $defaults = $this->getFieldDefaults($blueprint, true, $localization->locale());

            // Get the localized term data merged with the origin.
            $data = $localization->values();

            // Retain field sync status by removing any defaults that already exist on the origin.
            $defaultsToSet = $defaults->diffAssoc($data);

            // Don't merge data that already exists on the localized term.
            if ($defaultsToSet->isEmpty()) {
                return false;
            }

            return $localization->merge($defaultsToSet);
        })->filter();

        // Only save if there are new values.
        // TODO: This could probably be changed when saveQuietly() is available: https://github.com/statamic/cms/pull/3379
        if ($localizations->isNotEmpty()) {
            $event->term->save();
        }
    }

    protected function extendBlueprint(Event $event): Blueprint
    {
        $data = $this->getProperty($event);
        $blueprint = $this->getBlueprintFromEvent($event);

        // This data is used on "Create Entry" and "Create Term" views so that we can get the content defaults.
        if (! $data) {
            $data = [
                'type' => Str::before($event->blueprint->namespace(), '.'),
                'handle' => Str::after($event->blueprint->namespace(), '.'),
                'locale' => basename(request()->path()),
            ];
        }

        $seoBlueprint = OnPageSeoBlueprint::make()->data($data)->items();

        return $blueprint->ensureFieldsInSection($seoBlueprint, 'SEO');
    }
}
