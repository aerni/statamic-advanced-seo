<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\UpdateScripts\UpdateScript;

class MigrateOriginsConfig extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
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
    public function update(): void
    {
        Seo::all()->each(function (SeoSet $set) {
            $origins = $set->localizations()->map->get('origin')->filter();

            if ($origins->isEmpty()) {
                return;
            }

            $set->config()->origins($origins->all());

            $set->localizations()->each->remove('origin');

            $set->save();
        });

        $this->console()->info('Migrated origins to defaults configs.');
    }
}
