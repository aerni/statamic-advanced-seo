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

        $contents = array_replace_recursive(
            $event->blueprint->contents(),
            ContentSeoBlueprint::resolve($model)->contents()
        );

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

        // In the CP, only extend on routes where it's safe, for editable
        // contexts, for users with permission.
        return $this->isOnAllowedCpRoute($context)
            && $context->seoSet()->editable()
            && Gate::allows('seo.edit-content');
    }

    /**
     * The blueprint is dispatched for many CP routes. We want to extend it on
     * any entry/term-scoped route (edit, create, update, store, publish,
     * localize, revisions, preview, actions, etc.) plus the AI generate route.
     *
     * Match by route name with a prefix wildcard so we catch all current and
     * future sub-routes under entries/terms without enumerating each one. The
     * blueprint editor (statamic.cp.fields.blueprints.*) and addon CP routes
     * (statamic.cp.advanced-seo.sets.*) live under different prefixes, so they
     * don't match — no false positives from visually similar URLs.
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
}
