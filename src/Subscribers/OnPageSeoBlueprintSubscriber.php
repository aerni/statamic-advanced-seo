<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
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
    use GetsFieldDefaults; // TODO: Can we delete this class altogether?
    use ShouldHandleRoute;

    protected array $events = [
        Events\EntryBlueprintFound::class => 'handleBlueprintFound',
        Events\TermBlueprintFound::class => 'handleBlueprintFound',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function handleBlueprintFound(Event $event): void
    {
        $this->model = $this->determineModel($event);
        $this->handle = Str::after($event->blueprint->namespace(), '.');

        if (! $this->shouldHandleBlueprintFound()) {
            return;
        }

        $this->extendBlueprintForCp($event);
        $this->extendBlueprintForFrontend($event);
    }

    protected function shouldHandleBlueprintFound(): bool
    {
        // Don't add any fields in the blueprint builder.
        if (Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'blueprints'])) {
            return false;
        }

        // Don't add any fields on custom views.
        if (Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'advanced-seo'])) {
            return false;
        }

        // Don't add fields if the collection/taxonomy is excluded in the config.
        if (in_array($this->handle, config("advanced-seo.disabled.{$this->model}", []))) {
            return false;
        }

        return true;
    }

    protected function extendBlueprintForCp(Event $event): void
    {
        if (! $this->isCpRoute()) {
            return;
        }

        // Has a value if editing or localizing an existing entry/term.
        $id = $this->model === 'collections'
            ? $event->entry?->id()
            : $event->term?->slug();

        // The locale a new entry is being created in.
        $createLocale = Arr::get(Site::all()->map->handle(), basename(request()->path()));

        /**
         * The BlueprintFound event is called for every localization.
         * But we only want to extend the blueprint for the current localization.
         * Otherwise the default values of the blueprint fields will be set
         * for the localization of the first event calling this method.
         */
        if (! Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), $this->model, $id ?? $createLocale])) {
            return;
        }

        $this->extendBlueprint($event);
    }

    protected function extendBlueprintForFrontend(Event $event): void
    {
        if (! $this->isFrontendRoute()) {
            return;
        }

        $this->extendBlueprint($event);
    }

    protected function extendBlueprint(Event $event): Blueprint
    {
        $data = $this->getDataFromEvent($event);
        $blueprint = $this->getBlueprintFromEvent($event);

        $seoBlueprint = OnPageSeoBlueprint::make()->data($data)->items();

        return $blueprint->ensureFieldsInSection($seoBlueprint, 'SEO');
    }
}
