<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\SeoSets\SeoSet;
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
}
