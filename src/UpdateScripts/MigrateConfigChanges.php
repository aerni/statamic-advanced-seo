<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\UpdateScripts\UpdateScript;

class MigrateConfigChanges extends UpdateScript
{
    protected Collection $seoSets;

    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    public function update(): void
    {
        $this->seoSets = Seo::all();

        $this->migrateDisabledConfig();
        $this->migrateOriginsConfig();
        $this->migrateSitemapsConfig();
        $this->saveSetsAndLocalizations();
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
    protected function migrateDisabledConfig(): void
    {
        collect(config('advanced-seo.disabled.collections'))
            ->each(function (string $handle) {
                $set = $this->seoSets
                    ->first(fn (SeoSet $set) => $set->id() === "collections::{$handle}");

                if ($set) {
                    $set->config()->enabled(false);
                }
            });

        collect(config('advanced-seo.disabled.taxonomies'))
            ->each(function (string $handle) {
                $set = $this->seoSets->first(fn (SeoSet $set) => $set->id() === "taxonomies::{$handle}");

                if ($set) {
                    $set->config()->enabled(false);
                }
            });

        $this->console()->info('Migrated disabled collections/taxonomies configs.');
    }

    /**
     * Migrate origin configuration from localizations to config
     *
     * In previous versions, origin information was stored in individual localizations.
     * In v3.0.0, origins are now managed centrally in the SeoSet config.
     *
     * This migration:
     * - Collects all 'origin' values from each SeoSet's localizations
     * - Stores them in the SeoSet's config using the origins() method
     * - Removes the 'origin' key from all localizations to prevent duplication
     *
     * This centralizes origin management and makes it easier to configure which
     * localizations should inherit SEO data from other localizations.
     */
    protected function migrateOriginsConfig(): void
    {
        $this->seoSets->each(function (SeoSet $set) {
            $localizations = $set->localizations();

            $origins = $localizations->map->get('origin')->filter()->all();

            $set->config()->origins($origins);

            $localizations->each->remove('origin');
        });

        $this->console()->info('Migrated origins configs.');
    }

    /**
     * Migrate sitemap exclusions from site indexing defaults to individual collection/taxonomy configs
     *
     * Old configuration:
     * - Sitemap exclusions were managed centrally in the site indexing defaults
     * - excluded_collections and excluded_taxonomies fields stored which handles to exclude per localization
     *
     * New configuration:
     * - Each collection/taxonomy SeoSet manages its own sitemap inclusion via config
     * - Per-localization control using the 'seo_sitemap_enabled' field in individual localizations
     *
     * This migration:
     * 1. Builds exclusion maps from the indexing set's excluded_collections and excluded_taxonomies
     *    - Maps which localizations have each collection/taxonomy excluded from sitemaps
     * 2. For each collection/taxonomy:
     *    - If excluded in all localizations: sets sitemap config to false
     *    - If excluded in some localizations: sets sitemap config to true, then disables per-localization
     * 3. Removes the old excluded_collections and excluded_taxonomies fields from indexing localizations
     *
     * This provides more granular control over sitemap inclusion at both the config and localization level,
     * and moves sitemap configuration closer to the content it describes.
     */
    protected function migrateSitemapsConfig(): void
    {
        $set = $this->seoSets->first(fn ($set) => $set->id() === 'site::indexing');

        if (! config('advanced-seo.sitemap.enabled', true)) {
            $set->localizations()->each(function ($localization) {
                $localization->remove('excluded_collections')->remove('excluded_taxonomies');
            });

            return;
        }

        // Explicitly enable sitemap and disable later if excluded.
        $this->seoSets
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->each(fn (SeoSet $set) => $set->config()->set('sitemap', true));

        $excludedCollections = $this->buildSitemapExclusionMap($set, 'excluded_collections');
        $excludedTaxonomies = $this->buildSitemapExclusionMap($set, 'excluded_taxonomies');

        $this->migrateSitemapType('collections', $excludedCollections);
        $this->migrateSitemapType('taxonomies', $excludedTaxonomies);

        $set->localizations()->each(function ($localization) {
            $localization->remove('excluded_collections')->remove('excluded_taxonomies');
        });

        $this->console()->info('Migrated sitemap config from site to collections/taxonomies configs.');
    }

    /**
     * Creates a map of localizations excluded from the sitemap keyed by the handle of the collection/taxonomy.
     *
     * Example return format:
     * [
     *     'pages' => ['default', 'german'],
     *     'tags' => ['french'],
     * ]
     */
    protected function buildSitemapExclusionMap(SeoSet $set, string $field): Collection
    {
        return $set
            ->localizations()
            ->map(fn (SeoSetLocalization $localization, $site) => $localization->value($field))
            ->filter()
            ->map(fn ($handles, $site) => ['handles' => $handles])
            ->groupBy('handles', true)
            ->map(fn ($sites) => $sites->keys());
    }

    protected function migrateSitemapType(string $type, Collection $handles): void
    {
        $handles
            ->map(fn ($sites, $handle) => $this->seoSets->first(fn ($set) => $set->id() === "{$type}::{$handle}"))
            ->filter()
            ->each(function ($set, $handle) use ($handles) {
                $localizationsWithDisabledSitemap = $handles[$handle];
                $setSites = $set->sites()->keys();
                $localizationsWithEnabledSitemap = $setSites->diff($localizationsWithDisabledSitemap);

                if ($localizationsWithEnabledSitemap->isEmpty()) {
                    $set->config()->set('sitemap', false);

                    return;
                }

                $localizationsWithDisabledSitemap->each(fn ($site) => $set->in($site)->set('seo_sitemap_enabled', false));
            });
    }

    /**
     * Save all modified SeoSet configs and localizations.
     *
     * This is called once at the end of all migrations to ensure:
     * 1. All data transformations are complete before persistence
     * 2. Event listeners (HandleSeoSetConfigSaved) only run once per set
     * 3. Blueprint field filtering in fileData() occurs after migrations
     *
     * We save ALL localizations (both persisted and new) to ensure complete
     * configuration integrity, as some localizations modified during migration
     * may not have been previously persisted.
     */
    protected function saveSetsAndLocalizations(): void
    {
        $this->seoSets->each(function (SeoSet $set) {
            // Save config first (triggers HandleSeoSetConfigSaved event)
            $set->config()->save();

            // Explicitly save ALL localizations for this set
            // This ensures both previously persisted and newly modified localizations are saved
            $set->localizations()->each->save();
        });
    }
}
