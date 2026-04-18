<?php

namespace Aerni\AdvancedSeo\UpdateScripts\V3;

use Aerni\AdvancedSeo\Eloquent\SeoSetConfigModel;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalizationModel;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;

class MigrateConfigChanges
{
    protected Collection $seoSets;

    protected array $oldSiteSetHandles = ['general', 'indexing', 'social_media', 'analytics', 'favicons'];

    public function run(): void
    {
        /**
         * Existing v2 users get upgraded to Pro since they paid for all features.
         * This must happen first so that pro-gated features work during migration.
         */
        $this->enableProEdition();

        /**
         * For Eloquent users, we need to run migrations first to create the new tables
         * and migrate data from the old table before we can work with the new architecture.
         */
        if ($this->usesEloquentDriver()) {
            $this->migrateEloquentTables();
        }

        /**
         * Consolidate the 5 old site sets (general, indexing, social_media, analytics, favicons)
         * into a single site::defaults set. This must happen before Seo::all() so the merged
         * data is available for subsequent migrations.
         */
        $this->consolidateSiteSeoSets();

        $this->seoSets = Seo::all();

        $this->migrateFileStorageData();
        $this->migrateDisabledConfig();
        $this->migrateSitemapsConfig();
        $this->migrateSocialImagesGeneratorConfig();
        $this->saveSetsAndLocalizations();
    }

    protected function enableProEdition(): void
    {
        $configPath = config_path('statamic/editions.php');

        if (! File::exists($configPath)) {
            return;
        }

        $contents = File::get($configPath);

        if (Str::contains($contents, "'aerni/advanced-seo'")) {
            return;
        }

        $contents = preg_replace(
            "/'addons'\s*=>\s*\[\s*(?:\/\/\s*)?\n/",
            "'addons' => [\n        'aerni/advanced-seo' => 'pro',\n",
            $contents,
            1,
        );

        File::put($configPath, $contents);
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
    }

    /**
     * Consolidate the 5 old site SEO sets into a single site::defaults set.
     *
     * Operates at the storage level (files or DB) since the v3 domain model
     * can't load orphaned sets whose SeoSet doesn't exist in the registry.
     */
    protected function consolidateSiteSeoSets(): void
    {
        $this->usesEloquentDriver()
            ? $this->consolidateEloquentSiteSeoSets()
            : $this->consolidateFileSiteSeoSets();
    }

    protected function consolidateFileSiteSeoSets(): void
    {
        $directory = Stache::store('seo-set-configs')->directory().'site';

        if (! File::isDirectory($directory)) {
            return;
        }

        $this->consolidateFileSiteConfigs($directory);
        $this->consolidateFileSiteLocalizations($directory);
    }

    /**
     * Delete old site config files.
     */
    protected function consolidateFileSiteConfigs(string $directory): void
    {
        collect($this->oldSiteSetHandles)
            ->map(fn ($handle) => "{$directory}/{$handle}.yaml")
            ->filter(fn ($path) => File::exists($path))
            ->each(fn ($path) => File::delete($path));
    }

    /**
     * For each locale directory, merge all old localization files into a single defaults.yaml.
     */
    protected function consolidateFileSiteLocalizations(string $directory): void
    {
        collect(File::directories($directory))->each(function (string $directory) {
            $mergedData = collect($this->oldSiteSetHandles)
                ->map(fn ($handle) => "{$directory}/{$handle}.yaml")
                ->filter(fn ($path) => File::exists($path))
                ->reduce(function ($carry, $path) {
                    $data = array_merge($carry, YAML::file($path)->parse());

                    File::delete($path);

                    return $data;
                }, []);

            File::put("{$directory}/defaults.yaml", YAML::dump($mergedData));
        });
    }

    protected function consolidateEloquentSiteSeoSets(): void
    {
        $this->consolidateEloquentSiteConfigs();
        $this->consolidateEloquentSiteLocalizations();
    }

    protected function consolidateEloquentSiteConfigs(): void
    {
        $oldConfigs = SeoSetConfigModel::where('type', 'site')
            ->whereIn('handle', $this->oldSiteSetHandles)
            ->get();

        if ($oldConfigs->isEmpty()) {
            return;
        }

        $origins = $oldConfigs->firstWhere('data.origins')?->data['origins'];

        SeoSetConfigModel::updateOrCreate(
            ['type' => 'site', 'handle' => 'defaults'],
            ['data' => $origins ? ['origins' => $origins] : []],
        );

        SeoSetConfigModel::where('type', 'site')
            ->whereIn('handle', $this->oldSiteSetHandles)
            ->delete();
    }

    protected function consolidateEloquentSiteLocalizations(): void
    {
        $oldLocalizations = SeoSetLocalizationModel::where('type', 'site')
            ->whereIn('handle', $this->oldSiteSetHandles)
            ->get();

        if ($oldLocalizations->isEmpty()) {
            return;
        }

        $oldLocalizations->groupBy('locale')->each(function ($localizations, $locale) {
            $mergedData = $localizations->flatMap(fn ($localization) => $localization->data ?? [])->all();

            SeoSetLocalizationModel::updateOrCreate(
                ['type' => 'site', 'handle' => 'defaults', 'locale' => $locale],
                ['data' => $mergedData],
            );
        });

        SeoSetLocalizationModel::where('type', 'site')
            ->whereIn('handle', $this->oldSiteSetHandles)
            ->delete();
    }

    /**
     * Migrate file storage data that Eloquent users already handled via database migrations.
     *
     * These transformations are only needed for Stache users because the database migration
     * (2026_01_13_100002_migrate_seo_defaults_to_new_tables.php) already handles them
     * during the table migration for Eloquent users.
     */
    protected function migrateFileStorageData(): void
    {
        if ($this->usesEloquentDriver()) {
            return;
        }

        $this->migrateSingleSiteData();
        $this->removeTitleFromConfigSet();
        $this->migrateOriginsConfig();
    }

    /**
     * Migrate single-site data from config to localization.
     *
     * In v2, single-site installations stored SEO data directly in the config file
     * under a 'data' key. The new architecture stores this in separate localization files.
     *
     * This migration extracts the 'data' from each config and merges it into the
     * default site's localization. The data is saved when saveSetsAndLocalizations() runs.
     */
    protected function migrateSingleSiteData(): void
    {
        $setsWithData = $this->seoSets->filter(fn (SeoSet $set) => $set->config()->get('data'));

        if ($setsWithData->isEmpty()) {
            return;
        }

        $setsWithData->each(function (SeoSet $set) {
            $config = $set->config();
            $data = $config->get('data');

            $config->remove('data');
            $set->inDefaultSite()->merge($data);
        });
    }

    /**
     * Remove deprecated 'title' field from all SeoSet configs.
     *
     * The title field is no longer used in v3.0.0 and should be removed from all set configs.
     */
    protected function removeTitleFromConfigSet(): void
    {
        $this->seoSets->each(function (SeoSet $set) {
            $set->config()->remove('title');
        });
    }

    /**
     * Move origin values from individual localizations to centralized SeoSet config.
     */
    protected function migrateOriginsConfig(): void
    {
        $this->seoSets->each(function (SeoSet $set) {
            $localizations = $set->localizations();

            $origins = $localizations->map->get('origin')->filter()->all();

            $set->config()->origins($origins);

            $localizations->each->remove('origin');
        });
    }

    /**
     * Migrate disabled collections/taxonomies from centralized config arrays to per-set config.
     */
    protected function migrateDisabledConfig(): void
    {
        $this->disableSeoSetsFromConfig('collections', config('advanced-seo.disabled.collections'));
        $this->disableSeoSetsFromConfig('taxonomies', config('advanced-seo.disabled.taxonomies'));
    }

    protected function disableSeoSetsFromConfig(string $type, ?array $handles): void
    {
        collect($handles)->each(function (string $handle) use ($type) {
            $set = $this->seoSets->first(fn (SeoSet $set) => $set->id() === "{$type}::{$handle}");

            if ($set) {
                $set->config()->enabled(false);
            }
        });
    }

    /**
     * Migrate sitemap configuration from centralized exclusion lists to per-collection/taxonomy control.
     */
    protected function migrateSitemapsConfig(): void
    {
        $siteDefaults = $this->siteDefaultsSet();

        if (! config('advanced-seo.sitemap.enabled', true)) {
            $this->removeFromLocalizations($siteDefaults, ['excluded_collections', 'excluded_taxonomies']);

            return;
        }

        $this->seoSets
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->filter(fn (SeoSet $set) => $set->enabled())
            ->each(fn (SeoSet $set) => $set->config()->set('sitemap', true));

        $excludedCollections = $this->buildLocalizationHandleMap($siteDefaults, 'excluded_collections');
        $excludedTaxonomies = $this->buildLocalizationHandleMap($siteDefaults, 'excluded_taxonomies');

        $this->migrateSitemapType('collections', $excludedCollections);
        $this->migrateSitemapType('taxonomies', $excludedTaxonomies);

        $this->removeFromLocalizations($siteDefaults, ['excluded_collections', 'excluded_taxonomies']);
    }

    protected function migrateSitemapType(string $type, Collection $excludedHandles): void
    {
        $excludedHandles->each(function (Collection $excludedSites, string $handle) use ($type) {
            $set = $this->seoSets->first(fn ($set) => $set->id() === "{$type}::{$handle}");

            if (! $set || ! $set->enabled()) {
                return;
            }

            $enabledSites = $set->sites()->keys()->diff($excludedSites);

            if ($enabledSites->isEmpty()) {
                $set->config()->set('sitemap', false);

                return;
            }

            $excludedSites->each(fn ($site) => $set->in($site)->set('seo_sitemap_enabled', false));
        });
    }

    /**
     * Migrate social images generator from centralized collection list to per-collection control.
     */
    protected function migrateSocialImagesGeneratorConfig(): void
    {
        $siteDefaults = $this->siteDefaultsSet();

        if (! config('advanced-seo.social_images.generator.enabled', true)) {
            $this->removeFromLocalizations($siteDefaults, ['social_images_generator_collections']);

            return;
        }

        $this->seoSets
            ->filter(fn (SeoSet $set) => $set->type() === 'collections')
            ->filter(fn (SeoSet $set) => $set->enabled())
            ->each(fn (SeoSet $set) => $set->config()->set('social_images_generator', false));

        $enabledCollectionsMap = $this->buildLocalizationHandleMap($siteDefaults, 'social_images_generator_collections');

        $this->migrateSocialImagesGenerator($enabledCollectionsMap);

        $this->removeFromLocalizations($siteDefaults, ['social_images_generator_collections']);
    }

    /**
     * Enable social images generator for collections in the enabled map.
     *
     * Sets config to true and explicitly sets localization defaults to false
     * for backward compatibility (preserves existing true values).
     */
    protected function migrateSocialImagesGenerator(Collection $enabledHandles): void
    {
        $enabledHandles->keys()->each(function (string $handle) {
            $set = $this->seoSets->first(fn ($set) => $set->id() === "collections::{$handle}");

            if (! $set || ! $set->enabled()) {
                return;
            }

            $set->config()->set('social_images_generator', true);

            $set->localizations()->each(function ($localization) {
                if ($localization->get('seo_generate_social_images') !== true) {
                    $localization->set('seo_generate_social_images', false);
                }
            });
        });
    }

    /**
     * Save all modified SeoSet configs and localizations in a single pass.
     *
     * Called once at the end to ensure all transformations are complete before
     * persistence, and event listeners only fire once per set.
     */
    protected function saveSetsAndLocalizations(): void
    {
        $this->seoSets->each(function (SeoSet $set) {
            /**
             * Capture references before saving — $config->save() clears caches,
             * so we need to work with the same in-memory objects afterward.
             */
            $config = $set->config();
            $localizations = $set->localizations();

            $config->save();
            $localizations->each->save();
        });
    }

    protected function siteDefaultsSet(): SeoSet
    {
        return $this->seoSets->first(fn (SeoSet $set) => $set->id() === 'site::defaults');
    }

    /**
     * Build a map of handle => [sites] from a localization array field.
     *
     * Example: ['pages' => ['default', 'german'], 'tags' => ['french']]
     */
    protected function buildLocalizationHandleMap(SeoSet $set, string $field): Collection
    {
        $handleToSites = [];

        $set->localizations()->each(function (SeoSetLocalization $localization, string $site) use ($field, &$handleToSites) {
            foreach ($localization->value($field) ?? [] as $handle) {
                $handleToSites[$handle][] = $site;
            }
        });

        return collect($handleToSites)->map(fn ($sites) => collect($sites));
    }

    /**
     * Remove the given fields from all localizations of a set.
     *
     * @param  array<string>  $fields
     */
    protected function removeFromLocalizations(SeoSet $set, array $fields): void
    {
        $set->localizations()->each(function (SeoSetLocalization $localization) use ($fields) {
            foreach ($fields as $field) {
                $localization->remove($field);
            }
        });
    }
}
