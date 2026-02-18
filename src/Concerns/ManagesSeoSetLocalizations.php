<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\SeoLocalization;

trait ManagesSeoSetLocalizations
{
    /**
     * Save all existing (persisted) localizations.
     * This ensures the removal of data from disabled features via blueprintFields() filtering.
     */
    protected function saveExistingLocalizations(SeoSet $seoSet): void
    {
        SeoLocalization::whereSeoSet($seoSet->id())->each->save();
    }

    /**
     * Delete localizations for sites that no longer exist in the SEO Set.
     * Orphaned localizations occur when sites are removed from a collection or taxonomy.
     */
    protected function cleanupOrphanedLocalizations(SeoSet $seoSet): void
    {
        $validSites = $seoSet->sites()->keys();

        SeoLocalization::whereSeoSet($seoSet->id())
            ->reject(fn ($localization) => $validSites->contains($localization->locale()))
            ->each->delete();
    }

    /**
     * Delete all persisted localizations for this SEO Set.
     */
    protected function deleteLocalizations(SeoSet $seoSet): void
    {
        SeoLocalization::whereSeoSet($seoSet->id())->each->delete();
    }

    /**
     * Remove all seo_* field values from entries or terms.
     */
    protected function cleanupSeoValues(SeoSet $seoSet): void
    {
        RemoveSeoValues::handle($seoSet->parent());
    }

    /**
     * When the sitemap is disabled in the config,
     * remove the per-entry/term sitemap values.
     */
    protected function cleanupSitemapValues(SeoSetConfig $config): void
    {
        if ($config->value('sitemap')) {
            return;
        }

        RemoveSeoValues::handle($config->seoSet()->parent(), [
            'seo_sitemap_enabled',
            'seo_sitemap_priority',
            'seo_sitemap_change_frequency',
        ]);
    }

    /**
     * When the social images generator is disabled in the config,
     * remove the per-entry/term toggle and theme values.
     */
    protected function cleanupSocialImageGeneratorValues(SeoSetConfig $config): void
    {
        if ($config->value('social_images_generator')) {
            return;
        }

        RemoveSeoValues::handle($config->seoSet()->parent(), [
            'seo_generate_social_images',
            'seo_social_images_theme',
        ]);
    }
}
