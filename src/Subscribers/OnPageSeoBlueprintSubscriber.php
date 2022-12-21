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
use Statamic\Facades\Fieldset;
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

        // $this->data is used to show/hide fields under certain conditions.
        $seoFields = OnPageSeoBlueprint::make()->data($this->data)->items();

        // Get all the linked SEO fieldset fields that the user explicitly added to the blueprint.
        $linkedSeoFieldsetFields = $event->blueprint->fields()->all()
            ->filter(fn ($field) => $field->type() === 'advanced_seo') // Remove any field that isn't of type `advanced_seo`
            ->filter(fn ($field) => array_key_exists($field->config()['field'], $seoFields)); // Remove any field that isn't an actual SEO field

        /**
         * If the user didn't explicitly add any Advanced SEO fieldsets to the blueprint,
         * we want to add all fields that are part of the 'advanced-seo::main' fieldset to the SEO section.
         * This allows the user to configure the SEO fields that should be shown by default.
         * All SEO fields, that are not part of the fieldset will still be added but as hidden field.
         */
        if ($linkedSeoFieldsetFields->isEmpty()) {
            // The fields I want to show by default.
            $fieldset = Fieldset::find('advanced-seo::main')->fields()->all()->map(fn ($field) => $field->config());

            // The SEO fields that should be visible. This also includes any other fields the user might have added to the fieldset.
            $visibleFields = $fieldset->map(fn ($field, $handle) => collect($seoFields)->get($handle) ?? $field);

            // Add all visible fields to the blueprint.
            $event->blueprint->ensureFieldsInSection($visibleFields, 'SEO');

            // All other SEO fields that are not part of the fieldset and should not be visible.
            $hiddenFields = collect($seoFields)->diffKeys($fieldset);

            // Add all hidden fields to the blueprint.
            $hiddenFields
                ->map(fn ($config) => array_merge($config, ['visibility' => 'hidden']))
                ->each(fn ($config, $handle) => $event->blueprint->ensureField($handle, $config));

            return;
        }

        /**
         * Override each linked 'advanced_seo' field with the corresponding field from the OnPageSeoBlueprint.
         * Note, that this only works with linked fieldsets. It doesn't work with single linked fields.
         */
        $linkedSeoFieldsetFields = $linkedSeoFieldsetFields->mapWithKeys(function ($field) use ($seoFields) {
            $handle = $field->config()['field'];
            $config = $seoFields[$handle];

            return [$handle => $field->setHandle($handle)->setConfig($config)];
        });

        /**
         * To ensure the addon's functionality, we need to add all the remaining SEO fields that were not added through a linked fieldset.
         * But we'll hide those fields so that it's up to the admin to explicitly add fields that should be editable.
         */
        collect($seoFields)->diffKeys($linkedSeoFieldsetFields)
            ->map(fn ($config) => array_merge($config, ['visibility' => 'hidden']))
            ->each(fn ($config, $handle) => $event->blueprint->ensureField($handle, $config));
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
