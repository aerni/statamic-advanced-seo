<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\TextUI\Help;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Site;
use Statamic\Statamic;

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

        // Only extend it once per data.

        /**
         * The following code determines which seo fields should be added to the blueprint.
         * This is an important concept, as we will only return data of ensured blueprint fields
         * in the ViewCascade and GraphQlCascade. Data of fields with no blueprint field won't be augmented.
         */

        /**
         * These are the fields that we will end up adding to the blueprint.
         * We are passing $this->data to evaluate the feature of each field to determine if a field should be in the blueprint or not.
         */
        $seoFields = OnPageSeoBlueprint::make()->data($this->data)->get()->fields()->all();

        // TODO: This check fails because the blueprint fields contain ensured fields of previous cycles.
        $usesDefaultSeoBlueprint = $seoFields
            ->intersectByKeys($event->blueprint->fields()->all())
            ->isEmpty();

        $usesDefaultSeoBlueprint
            ? $this->handleDefaultBlueprint($event, $seoFields)
            : $this->handleCustomizedBlueprint($event, $seoFields);
    }

    /**
     * If the user didn't explicitly add any Advanced SEO fieldsets to the blueprint,
     * we want to add all fields that are part of the 'advanced-seo::main' fieldset to the SEO section.
     * This allows the user to configure the SEO fields that should be shown by default by editing the main fieldset.
     * Any SEO field, that doesn't exist in the fieldset will be added as a hidden field to ensure the addon's functionality.
     */
    protected function handleDefaultBlueprint(Event $event, Collection $seoFields): void
    {
        // The fields that should be shown by default.
        $seoFieldsetFields = Fieldset::find('advanced-seo::main')->fields()->all();

        // Swap the fieldset fields with the actual field from the OnPageSeoBlueprint.
        $seoFieldsWithConfig = $seoFieldsetFields
            ->filter(fn ($field) => $field->type() === 'advanced_seo') // Remove any field that isn't of type `advanced_seo`
            ->filter(fn ($field) => $seoFields->has($field->config()['field'])) // Remove any field that isn't an actual SEO field
            ->map(fn ($field) => $seoFields->get($field->config()['field'])) // Get the field from the OnPageSeoBlueprint fields
            ->mapWithKeys(fn ($field) => [$field->handle() => $field]) // Make sure to set the correct handle
            ->map(fn ($field) => $field->config()); // We need the config to ensure the field below

        // All non-SEO fields the user might have added to the fieldset.
        $customUserFields = $seoFieldsetFields
            ->filter(fn ($field) => $field->type() !== 'advanced_seo')
            ->map(fn ($field) => $field->config());

        // Merge the SEO fields with the user-defined fields.
        $allFields = $seoFieldsWithConfig->merge($customUserFields);

        // The fields that should be visible in the CP in the correct order.
        $visibleFields = $seoFieldsetFields
            ->intersectByKeys($allFields) // Respect each field's position from the fieldset.
            ->merge($allFields); // Merge the preparated fields.

        // Add all visible fields to the blueprint.
        $event->blueprint->ensureFieldsInTab($visibleFields, 'SEO');

        // All other SEO fields that are not part of the fieldset and should not be visible.
        $fieldsToHide = collect($seoFields)->diffKeys($visibleFields);

        $this->ensureHiddenFields($event, $fieldsToHide);
    }

    /**
     * Override each linked 'advanced_seo' field with the corresponding field from the OnPageSeoBlueprint.
     * Note, that this only works with linked fieldsets and regular fields. It doesn't work with single linked fieldset fields.
     */
    protected function handleCustomizedBlueprint(Event $event, Collection $seoFields): void
    {
        // The Advanced SEO fields the user explicitly added to the blueprint.
        $manuallyAddedSeoFields = $event->blueprint->fields()->all()
            ->filter(fn ($field) => $field->type() === 'advanced_seo') // Remove any field that isn't of type `advanced_seo`
            ->filter(fn ($field) => $seoFields->has($field->config()['field'])); // Remove any field that isn't an actual SEO field

        // Custom SEO fields like a computed text field for `seo_title`.
        $customSeoFields = $event->blueprint->fields()->all()
            ->intersectByKeys($seoFields) // Only keep fields that exist as SEO fields
            ->diffKeys($manuallyAddedSeoFields); // Only keep fields that were not already imported

        // Handle individual Advanced SEO fields
        $individualAdvancedSeoFields = $event->blueprint->fields()->items()
            ->filter(fn ($item) => Arr::has($item, 'handle') && is_array($item['field'])) // Remove imported fieldsets and sinlge imported fieldset fields.
            ->mapWithKeys(fn ($item) => [$item['handle'] => $item['field']])
            ->intersectByKeys($manuallyAddedSeoFields) // Only keep the field that are actual Advanced SEO fields
            ->map(fn ($field) => $seoFields->get($field['field']))
            ->each(fn ($field) => $event->blueprint->ensureFieldHasConfig($field->handle(), $field->config()));

        // Handle imported fieldset fields
        $importedFieldsetFields = $manuallyAddedSeoFields
            ->diffKeys($individualAdvancedSeoFields)
            ->each(function ($field) use ($seoFields) {
                $seoField = $seoFields[$field->config()['field']];
                $handle = $seoField->handle();
                $config = $seoField->config();

                $field->setHandle($handle)->setConfig($config);
            });

        // All the fields that are visible
        $visibleFields = $individualAdvancedSeoFields
            ->merge($importedFieldsetFields)
            ->merge($customSeoFields);

        // All other SEO fields that are not part of the fieldset and should not be visible.
        $fieldsToHide = collect($seoFields)->diffKeys($visibleFields);

        $this->ensureHiddenFields($event, $fieldsToHide);
    }

    /**
     * We need to ensure that all SEO fields are added to the blueprint to ensure the addon's functionality.
     * But we'll hide those fields so that it's up to the user to decide which fields should be editable.
     */
    protected function ensureHiddenFields(Event $event, Collection $fieldsToHide): void
    {
        $fieldsToHide->map(fn ($field) => $field->config()) // We need the config to ensure the field below.
            ->map(fn ($config) => array_merge($config, ['if' => ['hide_me_and_do_not_save_data' => true]])) // Add condition to hide the field and save no data.
            ->each(fn ($config, $handle) => $event->blueprint->ensureField($handle, $config));
    }

    protected function shouldExtendBlueprint(Event $event): bool
    {
        if ($this->isDisabledType()) {
            return false;
        }

        if ($this->isDisallowedRoute()) {
            ray('disallowed route');
            return false;
        }

        // if (! $this->isAllowedRoute()) {
        //     // ray($event, request()->route()->getName(), $this->data)->label('Disallowed CP Route');
        //     return false;
        // }

        /**
         * The BlueprintFound event is called for every localization.
         * But we only want to extend the blueprint for the current localization.
         * Otherwise we will have issue evaluating conditional fields, e.g. the sitemap fields.
         */

         // TODO: Maybe we don't even need this as it is only called once anyways?
        if (Helpers::isEntryCreateRoute()) {
            ray($event, basename(request()->path()) === request()->route()->originalParameter('site'))->label('Entry Create Route');
            return basename(request()->path()) === request()->route()->originalParameter('site');
        }

        if (Helpers::isEntryEditRoute()) {
            ray($event, $event->entry?->id() === request()->route()->originalParameter('entry'))->label('Entry Edit Route');
            return $event->entry?->id() === request()->route()->originalParameter('entry');
        }

        if (Helpers::isTermCreateRoute()) {
            ray($event)->label('Term Create Route');
            // return basename(request()->path()) === request()->route()->originalParameter('site');
        }

        // TODO: Might not need this as a localized terms share the same blueprint.
        // But we still only want to extend it with the correct locale data. So how does it work?
        if (Helpers::isTermEditRoute()) {
            ray($event)->label('Term Edit Route');
            // return $event->term?->slug() === request()->route()->originalParameter('term');
        }

        ray($event)->label('Passed');

        // // Don't add fields to any other CP route other than the entry/term view and when performing an action on the listing view (necesarry for the social images generator action to work).
        // if (Statamic::isCpRoute() && ! $this->isModelCpRoute($event) && ! $this->isActionCpRoute()) {
        //     return false;
        // }

        return true;
    }

    protected function isDisabledType(): bool
    {
        return in_array($this->data->handle, config("advanced-seo.disabled.{$this->data->type}", []));
    }

    protected function isDisallowedRoute(): bool
    {
        // TODO: Why am I disallowing those routes in the first place?
        // The blueprint route I think is to prevent the fields being saved to the blueprint?
        return collect([
            Helpers::isBlueprintCpRoute(),
            Helpers::isAddonCpRoute()
        ])->filter()->isNotEmpty();
    }

    protected function isAllowedRoute(): bool
    {
        // TODO: It gets too complicated having to whitelist every possible route.
        // Can we remove this and default to extending the blueprint while making sure to only do it once?
        return in_array(request()->route()?->getName(), [
            'statamic.site',
            'statamic.cp.collections.entries.create',
            'statamic.cp.collections.entries.edit',
            'statamic.cp.collections.entries.actions.run',
            'statamic.cp.taxonomies.terms.create',
            'statamic.cp.taxonomies.terms.edit',
            'statamic.cp.taxonomies.terms.actions.run',
            // TODO: Add the social images route?
            // TODO: Add GraphQL route?
        ]);
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
}
