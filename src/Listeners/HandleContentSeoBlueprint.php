<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Blueprints\ContentSeoBlueprint;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Context\Context;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Statamic;

class HandleContentSeoBlueprint
{
    use GetsEventData;

    public function handleEntryBlueprintFound(EntryBlueprintFound $event): void
    {
        $this->extendBlueprint($event);
    }

    public function handleTermBlueprintFound(TermBlueprintFound $event): void
    {
        $this->extendBlueprint($event);
    }

    protected function extendBlueprint(EntryBlueprintFound|TermBlueprintFound $event): void
    {
        if (! $this->shouldExtendBlueprint($event)) {
            return;
        }

        /**
         * Fall back to the event itself when the entry/term property is null,
         * e.g. during entry/term creation where no model exists yet.
         */
        $model = $this->getProperty($event) ?? $event;

        $seoContents = ContentSeoBlueprint::resolve($model)->contents();

        /**
         * In the CP, the gate decides publish-form visibility. When it denies
         * (non-editable SeoSet OR user without seo.edit-content), the fields are
         * still extended — just hidden from editors. Automated processes keep
         * reading the fields; only the UI affordance disappears.
         */
        if (Statamic::isCpRoute() && Gate::denies('seo.edit-content', Context::from($model)->seoSet())) {
            $seoContents = $this->hideSeoFields($seoContents);
        }

        $contents = array_replace_recursive($event->blueprint->contents(), $seoContents);

        // Quick and dirty solution to ensure we capitalize the tab title
        $contents['tabs']['seo']['display'] = 'SEO';

        $event->blueprint->setContents($contents);
    }

    protected function shouldExtendBlueprint(EntryBlueprintFound|TermBlueprintFound $event): bool
    {
        $context = $this->resolveEventContext($event);

        // SEO must be enabled for this context (universal).
        if (! $context->seoSet()->enabled()) {
            return false;
        }

        // Frontend requests always get the extended blueprint.
        if (! Statamic::isCpRoute()) {
            return true;
        }

        // In the CP, only extend on routes where it's safe.
        return $this->isOnAllowedCpRoute($context);
    }

    /**
     * Match all entry/term-scoped CP routes (plus AI generate) by name prefix
     * so we don't have to enumerate every sub-route (edit, create, publish, …).
     */
    protected function isOnAllowedCpRoute(Context $context): bool
    {
        $contentType = match ($context->type) {
            'collections' => 'entries',
            'taxonomies' => 'terms',
            default => null,
        };

        if ($contentType === null) {
            return false;
        }

        return Str::is([
            "statamic.cp.{$context->type}.{$contentType}.*",
            'statamic.cp.advanced-seo.ai.generate',
        ], request()->route()?->getName());
    }

    /**
     * Hide SEO fields in the publish form while keeping them in the blueprint
     * so automated reads (social image generation, sitemap) still work.
     */
    protected function hideSeoFields(array $seoContents): array
    {
        foreach ($seoContents['tabs']['seo']['sections'] as &$section) {
            foreach ($section['fields'] as &$field) {
                $field['field']['visibility'] = 'hidden';
                unset($field['field']['validate']); // Strip rules editors can't resolve on a hidden field.
            }
        }

        return $seoContents;
    }
}
