<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;
use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationDeleted;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationSaved;
use Illuminate\Events\Dispatcher;
use Statamic\Facades\Blink;

class FlushBlinkCachesSubscriber
{
    /**
     * Register event listeners for cache clearing.
     *
     * Clears all Advanced SEO Blink caches when configs or localizations
     * are saved or deleted to ensure fresh data on subsequent reads.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            SeoSetConfigSaved::class => 'clearCaches',
            SeoSetConfigDeleted::class => 'clearCaches',
            SeoSetLocalizationSaved::class => 'clearCaches',
            SeoSetLocalizationDeleted::class => 'clearCaches',
        ];
    }

    /**
     * Clear all Advanced SEO Blink caches.
     *
     * This includes:
     * - Config caches (advanced-seo::{set-id}::config)
     * - Localization caches (advanced-seo::{set-id}::localizations)
     * - Parent caches (advanced-seo::{set-id}::parent)
     * - Feature evaluation caches (advanced-seo::features::*)
     *
     * Stache automatically updates its own cache during save,
     * so we only need to clear Blink caches.
     */
    public function clearCaches(): void
    {
        Blink::flushStartingWith('advanced-seo::');
    }
}
