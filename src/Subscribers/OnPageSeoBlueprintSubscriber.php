<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Traits\GetsContentDefaults;
use Aerni\AdvancedSeo\Traits\GetsEventData;
use Aerni\AdvancedSeo\Traits\GetsFieldsWithDefault;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;
    use GetsFieldsWithDefault;
    use GetsContentDefaults;

    protected array $events = [
        Events\EntryBlueprintFound::class => 'handleEntryBlueprintFound',
        Events\TermBlueprintFound::class => 'handleTermBlueprintFound',
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

    // TODO: Check how this behaves for toggles.
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

    public function handleTermBlueprintFound(Event $event): void
    {
        if ($this->shouldHandleBlueprintEvents()) {
            $this->addTermDefaultsInCp($event);
            $this->addTermDefaultsToCascade($event);
        }
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

    // TODO: Check how this behaves for toggles.
    protected function addTermDefaultsToCascade(Event $event): void
    {
        // We only want to add data if we're on a Statamic frontend route.
        if (request()->route()->getName() === 'statamic.site') {

            // Extend the blueprint so that we can add data.
            $this->extendBlueprint($event);

            // Get the term's defaults.
            $defaults = collect($this->getContentDefaults($event->term))->map->raw();

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

    protected function extendBlueprint(Event $event): void
    {
        $data = $this->getProperty($event);

        // This data is used on "Create Entry" and "Create Term" views so that we can get the content defaults.
        $fallbackData = [
            'type' => Str::before($event->blueprint->namespace(), '.'),
            'handle' => Str::after($event->blueprint->namespace(), '.'),
            'locale' => basename(request()->path()),
        ];

        $blueprint = OnPageSeoBlueprint::make()->data($data ?? $fallbackData)->items();

        $event->blueprint->ensureFieldsInSection($blueprint, 'SEO');
    }
}
