<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Statamic\Facades\Role;
use Statamic\UpdateScripts\UpdateScript;

class MigrateUserPermissions extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    /**
     * Migrate old permissions to new ones
     *
     * Old permissios.
     * view seo {group} defaults
     * edit seo {group} defaults
     *
     * New permissions.
     * configure seo
     * edit seo
     *
     * The "edit seo {group} defaults" was renamed to "edit seo".
     * "edit seo" means users can view and edit SEO defaults (SeoSetLocalization) for all Statamic collection/taxonomies they have access to.
     *
     * The old "view seo {group} defaults" permission was removed in favor of the new "edit seo" permission.
     * There's no "view only" access anymore. If users can see the defaults, they can also edit them.
     *
     * A new "configure seo" permission was introduced.
     * This permission allows users to configure SEO settings (SeoSetConfig) for all Statamic collections/taxonomies they have access to.
     * Additionally, they get access to edit and configure site defaults (any SeoSet of type "site").
     *
     * If a user had any "view seo {group} defaults" permissions, they should get the "edit seo" permissions.
     * If a user had any "edit seo {group} defaults" permissions, they should get the "edit seo" permission.
     * If a user had the "edit seo site defaults" permission, they should get the "configure seo" permission.
     */
    public function update(): void
    {
        Role::all()->each(function ($role) {
            $permissions = $role->permissions();

            // Check if role has any old "view seo {group} defaults" or "edit seo {group} defaults" permissions
            $hasViewOrEditSeoDefaults = $permissions->contains(function ($permission) {
                return preg_match('/^(view|edit) seo .+ defaults$/', $permission);
            });

            // Check if role has "edit seo site defaults" permission
            $hasEditSeoSiteDefaults = $permissions->contains('edit seo site defaults');

            // Add "edit seo" permission if user had any view/edit permissions
            if ($hasViewOrEditSeoDefaults) {
                $role->addPermission('edit seo');
            }

            // Add "configure seo" permission if user had "edit seo site defaults"
            if ($hasEditSeoSiteDefaults) {
                $role->addPermission('configure seo');
            }

            // Remove all old permissions that match the pattern
            $permissions
                ->filter(fn ($permission) => preg_match('/^(view|edit) seo .+ defaults$/', $permission))
                ->each(fn ($oldPermission) => $role->removePermission($oldPermission));

            $role->save();
        });

        $this->console()->info('Migrated user permissions.');
    }
}
