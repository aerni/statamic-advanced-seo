<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Statamic\Events;
use Statamic\Statamic;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Support\Helpers;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;

    protected DefaultsData $data;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntryBlueprintFound::class => 'extendBlueprint',
            Events\TermBlueprintFound::class => 'extendBlueprint',
        ];
    }

    public function extendBlueprint(Event $event): void
    {
        $this->data = $this->getDataFromEvent($event);

        if (! $this->shouldExtendBlueprint($event)) {
            return;
        }

        $contents = array_replace_recursive(
            $event->blueprint->contents(),
            OnPageSeoBlueprint::make()->data($this->data)->get()->contents()
        );

        // Quick and dirty solution to ensure we capitalize the tab title
        $contents['tabs']['seo']['display'] = 'SEO';

        $event->blueprint->setContents($contents);
    }

    protected function shouldExtendBlueprint(Event $event): bool
    {
        // Don't add fields if the collection/taxonomy is excluded in the config.
        if (! $this->data->set()->enabled()) {
            return false;
        }

        // Don't add fields in the blueprint builder.
        if (Helpers::isBlueprintCpRoute()) {
            return false;
        }

        // Don't add fields on any addon views in the CP.
        if (Helpers::isAddonCpRoute()) {
            return false;
        }

        // Don't add fields to any other CP route other than the entry/term view and when performing an action on the listing view (necesarry for the social images generator action to work).
        if (Statamic::isCpRoute() && ! $this->isModelCpRoute($event) && ! $this->isActionCpRoute()) {
            return false;
        }

        return true;
    }

    protected function isModelCpRoute(Event $event): bool
    {
        // Has a value if editing or localizing an existing entry/term.
        $id = $this->data->type === 'collections' ? $event->entry?->id() : $event->term?->slug();

        // The locale a new entry is being created in.
        $createLocale = Arr::get(Site::all()->map->handle(), basename(request()->path()));

        /**
         * The BlueprintFound event is called for every localization.
         * But we only want to extend the blueprint for the current localization.
         * Otherwise we will have issue evaluating conditional fields, e.g. the sitemap fields.
         */
        return Statamic::isCpRoute() && Str::containsAll(request()->path(), [$this->data->type, $id ?? $createLocale]);
    }

    protected function isActionCpRoute(): bool
    {
        return Statamic::isCpRoute() && Str::containsAll(request()->path(), [$this->data->type, $this->data->handle, 'actions']);
    }
}
