<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Statamic;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;

    protected Context $context;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntryBlueprintFound::class => 'extendBlueprint',
            Events\TermBlueprintFound::class => 'extendBlueprint',
        ];
    }

    public function extendBlueprint(Event $event): void
    {
        $model = $this->getProperty($event) ?? $event;
        $this->context = $this->resolveEventContext($event);

        if (! $this->shouldExtendBlueprint($event)) {
            return;
        }

        $contents = array_replace_recursive(
            $event->blueprint->contents(),
            OnPageSeoBlueprint::resolve($model)->contents()
        );

        // Quick and dirty solution to ensure we capitalize the tab title
        $contents['tabs']['seo']['display'] = 'SEO';

        $event->blueprint->setContents($contents);
    }

    protected function shouldExtendBlueprint(Event $event): bool
    {
        // Don't add fields if the collection/taxonomy is excluded in the config.
        if (! $this->context->seoSet()->enabled()) {
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

        // Don't add fields to any other CP route other than the entry/term view and when performing an action on the listing view (necessary for the social images generator action to work).
        if (Statamic::isCpRoute() && ! $this->isModelCpRoute($event) && ! $this->isActionCpRoute()) {
            return false;
        }

        // Don't add fields if content editing is disabled for this collection/taxonomy.
        if (Statamic::isCpRoute() && ! $this->context->seoSet()->editable()) {
            return false;
        }

        // Check if user has permission to edit SEO content
        if (Statamic::isCpRoute() && Gate::denies('seo.edit-content')) {
            return false;
        }

        return true;
    }

    protected function isModelCpRoute(Event $event): bool
    {
        // Has a value if editing or localizing an existing entry/term.
        $id = $this->context->type === 'collections' ? $event->entry?->id() : $event->term?->slug();

        // The locale a new entry is being created in.
        $createLocale = Arr::get(Site::all()->map->handle(), basename(request()->path()));

        /**
         * The BlueprintFound event is called for every localization.
         * But we only want to extend the blueprint for the current localization.
         * Otherwise we will have issue evaluating conditional fields, e.g. the sitemap fields.
         */
        return Statamic::isCpRoute() && Str::containsAll(request()->path(), [$this->context->type, $id ?? $createLocale]);
    }

    protected function isActionCpRoute(): bool
    {
        return Statamic::isCpRoute() && Str::containsAll(request()->path(), [$this->context->type, $this->context->handle, 'actions']);
    }
}
