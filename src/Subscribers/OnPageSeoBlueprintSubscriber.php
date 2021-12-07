<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Traits\GetsContentDefaults;
use Aerni\AdvancedSeo\Traits\GetsEventData;
use Aerni\AdvancedSeo\Traits\GetsFieldDefaults;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Fields\Blueprint;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;
    use GetsFieldDefaults;
    use GetsContentDefaults;

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

    protected function shouldHandleBlueprintEvents(): bool
    {
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
        if ($this->shouldHandleBlueprintEvents()) {
            $this->addEntryDefaultsInCp($event);
            $this->addEntryDefaultsToCascade($event);
        }
    }

    public function handleEntrySaved(Event $event): void
    {
        /**
         * This is a workaround to save the Blueprint Fields defaults to the entry when first creating it.
         * See issue here: https://github.com/statamic/cms/issues/4867
         */
        $this->saveEntryDefaults($event);
    }

    protected function addEntryDefaultsInCp(Event $event): void
    {
        /**
         * Add the fields to the entry blueprint in the CP. But only for the current localized entry.
         * This is to prevent that every localization adds fields to the blueprint.
         * If we don't do this check, we can't add the localized content defaults correctly.
         */
        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'collections', 'entries', $event->entry?->id() ?? 'create'])) {
            $this->extendBlueprint($event);
        }
    }

    protected function addEntryDefaultsToCascade(Event $event): void
    {
        // We only want to add data if we're on a Statamic frontend route.
        if (request()->route()->getName() === 'statamic.site') {

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
    }

    protected function saveEntryDefaults(Event $event): void
    {
        // Extend the blueprint so that we can add data.
        $blueprint = $this->extendBlueprint($event);

        // Get the entry's blueprint defaults.
        $defaults = $this->getFieldDefaults($blueprint, true);

        // Get the entry's data.
        $data = $event->entry->data();

        // We only want to set a default value if its key doesn't exist on the entry.
        $defaultsToSet = $defaults->diffKeys($data);

        // Don't save if the data already exists on the entry.
        if ($defaultsToSet->isEmpty()) {
            return;
        }

        $event->entry->merge($defaultsToSet)->saveQuietly();
    }

    public function handleTermBlueprintFound(Event $event): void
    {
        if ($this->shouldHandleBlueprintEvents()) {
            $this->addTermDefaultsInCp($event);
            $this->addTermDefaultsToCascade($event);
        }
    }

    public function handleTermSaved(Event $event): void
    {
        /**
         * This is a workaround to save the Blueprint Fields defaults to the term when first creating it.
         * See issue here: https://github.com/statamic/cms/issues/4867
         */
        $this->saveTermDefaults($event);
    }

    protected function addTermDefaultsInCp(Event $event): void
    {
        /**
         * Add the fields to the term blueprint in the CP. But only for the current localized term.
         * This is to prevent that every localization adds fields to the blueprint.
         * If we don't do this check, we can't add the localized content defaults correctly.
         */
        if (Str::containsAll(request()->path(), [config('cp.route', 'cp'), 'taxonomies', 'terms', $event->term?->slug() ?? 'create'])) {
            $this->extendBlueprint($event);
        }
    }

    protected function addTermDefaultsToCascade(Event $event): void
    {
        // We only want to add data if we're on a Statamic frontend route.
        if (request()->route()->getName() === 'statamic.site') {

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
    }

    protected function saveTermDefaults(Event $event): void
    {
        // Extend the blueprint so that we can add data.
        $blueprint = $this->extendBlueprint($event);

        $localizations = $event->term->localizations()->map(function ($localization) use ($blueprint) {
            // Get the localized term's blueprint defaults.
            $defaults = $this->getFieldDefaults($blueprint, true, $localization->locale());

            // Get the localized term's values (term + origin). Use a fresh copy of the origin so that we don't work with previously added data.
            $freshOriginData = $localization->term()->origin()->fresh()->data();
            $data = $freshOriginData->merge($localization->data());

            // We only want to set a default value if its key doesn't exist on the localized term.
            $defaultsToSet = $defaults->diffKeys($data);

            // Don't merge data that already exists on the localized term.
            if ($defaultsToSet->isEmpty()) {
                return false;
            }

            return $localization->merge($defaultsToSet);
        })->filter();

        // Only save if there are new values.
        if ($localizations->isNotEmpty()) {
            // TODO: Use saveQuietly() when it's available: https://github.com/statamic/cms/pull/3379
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
