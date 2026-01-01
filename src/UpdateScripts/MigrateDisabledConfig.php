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

    public function update(): void
    {
        collect(config("advanced-seo.disabled.collections"))
            ->each(function ($handle) {
                Seo::find("collections::{$handle}")?->config()->enabled(false)->save();
            });

        collect(config("advanced-seo.disabled.taxonomies"))
            ->each(function ($handle) {
                Seo::find("taxonomies::{$handle}")?->config()->enabled(false)->save();
            });

        $this->console()->info('Migrated disabled collections and taxonomies to set configs.');
    }
}
