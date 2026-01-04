<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\UpdateScripts\UpdateScript;

class MigrateDisabledConfig extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    /**
     * Migrate disabled collections and taxonomies from config to SeoSet config
     *
     * Old configuration:
     * - advanced-seo.disabled.collections (array of collection handles)
     * - advanced-seo.disabled.taxonomies (array of taxonomy handles)
     *
     * New configuration:
     * - Each SeoSet has its own enabled/disabled state in its config
     *
     * This migration:
     * - Reads the old config arrays for disabled collections and taxonomies
     * - Finds each corresponding SeoSet
     * - Sets enabled(false) on the SeoSet's config
     *
     * This allows per-collection/taxonomy control of SEO functionality
     * and removes the need for centralized disabled lists.
     */
    public function update(): void
    {
        collect(config('advanced-seo.disabled.collections'))
            ->each(function ($handle) {
                Seo::find("collections::{$handle}")?->config()->enabled(false)->save();
            });

        collect(config('advanced-seo.disabled.taxonomies'))
            ->each(function ($handle) {
                Seo::find("taxonomies::{$handle}")?->config()->enabled(false)->save();
            });

        $this->console()->info('Migrated disabled collections and taxonomies to defaults configs.');
    }
}
