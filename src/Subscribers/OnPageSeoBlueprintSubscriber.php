<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Statamic\Statamic;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;

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

        // The data is used to show/hide fields under certain conditions.
        $seoFields = OnPageSeoBlueprint::make()->data($this->data)->items();

        // This only works with linked fieldsets. It doesn't work with single imported fieldset fields or single advanced_seo fields.
        $existingSeoFields = $event->blueprint->fields()->all()
            ->filter(fn ($field) => $field->type() === 'advanced_seo') // Remove any fields other than of type `advanced_seo`
            ->filter(fn ($field) => array_key_exists($field->config()['field'], $seoFields)) // Remove any fields that are not part of the SEO fields
            ->each(function ($field) use ($seoFields) { // Override each field with the config from the SEO fields.
                $handle = $field->config()['field'];
                $config = $seoFields[$handle];
                $field->setHandle($handle)->setConfig($config);
            });

        // Add all SEO fields if the user didn't specify which fields he wants.
        if ($existingSeoFields->isEmpty()) {
            $event->blueprint->ensureFieldsInSection($seoFields, 'SEO');
        }

        /**
         * This only works with single linked fields and advanced_seo fields.
         * It doesn't work with linked fieldsets because of how imported fieldset configs are merged.
         */
        // $existingSeoFields->each(function ($field) use ($event) {
        //     $event->blueprint
        //         ->removeField($field->handle())
        //         ->ensureField($field->handle(), $field->config());
        // });

        // This doesn't have any effect. Would be nice if it did so we don't have to use ensureField().
        // $event->blueprint->field('seo_section_title_description')->setConfig(['type' => 'text']);

        // This doesn't work with linked fieldsets or single linked fieldset fields.
        // $event->blueprint->ensureFieldHasConfig('seo_section_title_description', ['display' => 'hi', 'type' => 'text']);
    }

    protected function shouldExtendBlueprint(Event $event): bool
    {
        // Don't add fields if the collection/taxonomy is excluded in the config.
        if ($this->isDisabledType()) {
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

        // Don't add fields for any requests other than the one of the current entry/term.
        if (Statamic::isCpRoute() && ! $this->isCurrentCpRoute($event)) {
            return false;
        }

        return true;
    }

    protected function isDisabledType(): bool
    {
        return in_array($this->data->handle, config("advanced-seo.disabled.{$this->data->type}", []));
    }

    protected function isCurrentCpRoute(Event $event): bool
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
        return Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), $this->data->type, $id ?? $createLocale]);
    }
}
