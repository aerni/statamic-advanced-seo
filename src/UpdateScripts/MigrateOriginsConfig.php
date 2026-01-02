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

        $this->console()->info('Migrated origins collections and taxonomies to set configs.');
    }
}
