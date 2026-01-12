<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
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
        // For Eloquent users, we need to run migrations first to create the new tables
        // and migrate data from the old table before we can work with the new architecture.
        if ($this->usesEloquentDriver()) {
            $this->migrateEloquentTables();
        }

        $this->seoSets = Seo::all();

        $this->removeTitleFromConfigSet();
        $this->migrateDisabledConfig();
        $this->migrateOriginsConfig();
        $this->migrateSitemapsConfig();
        $this->migrateSocialImagesGeneratorConfig();
        $this->saveSetsAndLocalizations();
    }

    protected function usesEloquentDriver(): bool
    {
        return Composer::isInstalled('statamic/eloquent-driver')
            && config('advanced-seo.driver') === 'eloquent';
    }

    /**
     * Publish and run the Eloquent migrations.
     *
     * This ensures the new tables are created and data is migrated from the old
     * advanced_seo_defaults table before the update script tries to work with
     * the new SeoSetConfig and SeoSetLocalization repositories.
     */
    protected function migrateEloquentTables(): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'advanced-seo-migrations',
            '--force' => true,
        ]);

        Artisan::call('migrate');

        $this->console()->info('Published and migrated Advanced SEO database tables.');
    }

    /**
     * Remove deprecated 'title' field from all SeoSet configs.
     *
     * The title field is no longer used in v3.0.0 and should be removed from all set configs.
     *
     * Note: For Eloquent users, this is already handled by the database migration
     * (2026_01_13_100002_migrate_advanced_seo_defaults_to_new_tables.php)
     */
    protected function removeTitleFromConfigSet(): void
    {
        if ($this->usesEloquentDriver()) {
            return;
        }

        $this->seoSets->each(function (SeoSet $set) {
            $set->config()->remove('title');
        });

        $this->console()->info('Removed deprecated title field from all set configs.');
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
     *
     * Note: For Eloquent users, this is already handled by the database migration
     * (2026_01_13_100002_migrate_advanced_seo_defaults_to_new_tables.php)
     */
    protected function migrateOriginsConfig(): void
    {
        if ($this->usesEloquentDriver()) {
            return;
        }

        $this->seoSets->each(function (SeoSet $set) {
            $localizations = $set->localizations();

            $origins = $localizations->map->get('origin')->filter()->all();

            $set->config()->origins($origins);

            $localizations->each->remove('origin');
        });

        $this->console()->info('Migrated origins configs.');
    }

    /**
     * Migrate sitemap configuration from centralized exclusion lists to per-collection/taxonomy control.
     *
     * Old: Centralized exclusion lists in site::indexing (excluded_collections, excluded_taxonomies)
     * New: Per-collection/taxonomy sitemap config with optional per-localization overrides
     *
     * Process:
     * 1. Early return if sitemap disabled: cleans up deprecated fields and skips migration
     * 2. Set all collections/taxonomies to sitemap enabled by default
     * 3. Build exclusion map from site::indexing localizations
     * 4. For excluded items: disable at config level (if all sites) or per-localization (if some sites)
     * 5. Remove deprecated fields from site::indexing localizations
     */
    protected function migrateSitemapsConfig(): void
    {
        $set = $this->seoSets->first(fn ($set) => $set->id() === 'site::indexing');

        if (! config('advanced-seo.sitemap.enabled', true)) {
            $set->localizations()->each(function ($localization) {
                $localization->remove('excluded_collections')->remove('excluded_taxonomies');
            });

            $this->console()->info('Removed deprecated sitemap config fields.');

            return;
        }

        // Explicitly enable sitemap and disable later if excluded.
        $this->seoSets
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->each(fn (SeoSet $set) => $set->config()->set('sitemap', true));

        $excludedCollections = $this->buildLocalizationHandleMap($set, 'excluded_collections');
        $excludedTaxonomies = $this->buildLocalizationHandleMap($set, 'excluded_taxonomies');

        $this->migrateSitemapType('collections', $excludedCollections);
        $this->migrateSitemapType('taxonomies', $excludedTaxonomies);

        $set->localizations()->each(function ($localization) {
            $localization->remove('excluded_collections')->remove('excluded_taxonomies');
        });

        $this->console()->info('Migrated sitemap config from site to collections/taxonomies configs.');
    }

    /**
     * Build a map grouping site handles by the collections/taxonomies they contain.
     *
     * Extracts handle arrays from a localization field and groups sites by which handles they contain.
     * Used by both sitemap and social images generator migrations.
     *
     * Returns: ['pages' => ['default', 'german'], 'tags' => ['french']]
     */
    protected function buildLocalizationHandleMap(SeoSet $set, string $field): Collection
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
     * Migrate social images generator from centralized collection list to per-collection control.
     *
     * Old: Centralized list in site::social_media (social_images_generator_collections)
     * New: Per-collection generator config with optional per-localization overrides
     *
     * Process:
     * 1. Early return if generator disabled: cleans up deprecated field and skips migration
     * 2. Set all collections to generator disabled by default
     * 3. Build enabled collections map from site::social_media localizations
     * 4. For enabled collections: enable at config level and set localization defaults to false for backward compatibility
     * 5. Remove deprecated field from site::social_media localizations
     */
    protected function migrateSocialImagesGeneratorConfig(): void
    {
        $socialMediaSet = $this->seoSets->first(fn ($set) => $set->id() === 'site::social_media');

        if (! config('advanced-seo.social_images.generator.enabled', true)) {
            $socialMediaSet->localizations()->each(function ($localization) {
                $localization->remove('social_images_generator_collections');
            });

            $this->console()->info('Removed deprecated social images generator config field.');

            return;
        }

        // Explicitly disable social images generator and enable later if configured.
        $this->seoSets
            ->filter(fn (SeoSet $set) => $set->type() === 'collections')
            ->each(fn (SeoSet $set) => $set->config()->set('social_images_generator', false));

        $enabledCollectionsMap = $this->buildLocalizationHandleMap($socialMediaSet, 'social_images_generator_collections');

        $this->migrateSocialImagesGenerator($enabledCollectionsMap);

        $socialMediaSet->localizations()->each(fn ($localization) => $localization->remove('social_images_generator_collections'));

        $this->console()->info('Migrated social images generator config from site to collections/taxonomies configs.');
    }

    /**
     * Enable social images generator for collections in the enabled map.
     *
     * Sets config to true and explicitly sets localization defaults to false for backward compatibility
     * (unless already explicitly set to true, which will be preserved).
     */
    protected function migrateSocialImagesGenerator(Collection $handles): void
    {
        $handles
            ->map(fn ($sites, $handle) => $this->seoSets->first(fn ($set) => $set->id() === "collections::{$handle}"))
            ->filter()
            ->each(function ($set) {
                $set->config()->set('social_images_generator', true);

                // Explicitly set false on all localizations unless already true
                // This ensures backward compatibility when the field default changes to true
                $set->localizations()->each(function ($localization) {
                    if ($localization->get('seo_generate_social_images') !== true) {
                        $localization->set('seo_generate_social_images', false);
                    }
                });
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
            // Get references to config and localizations before saving
            // This ensures we work with the same modified objects after caches are cleared on $config->save().
            $config = $set->config();
            $localizations = $set->localizations();

            // Save config first (triggers HandleSeoSetConfigSaved event)
            $config->save();

            // Explicitly save ALL localizations for this set
            // This ensures both previously persisted and newly modified localizations are saved
            $localizations->each->save();
        });
    }
}
