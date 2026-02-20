<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateConfigChanges;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateSeoFields;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateUserPermissions;
use Statamic\UpdateScripts\UpdateScript;

class MigrateToV3 extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    public function update(): void
    {
        (new MigrateConfigChanges)->run();
        (new MigrateSeoFields)->run();
        (new MigrateUserPermissions)->run();

        $this->console()->info('Successfully migrated to Advanced SEO v3.');
    }
}
