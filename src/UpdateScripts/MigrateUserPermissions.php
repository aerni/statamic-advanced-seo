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
     * Old permissions.
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
     * The old "view seo {group} defaults" permission is removed without replacement.
     * Users who only had "view" permissions will not receive any new SEO permissions.
     *
     * A new "configure seo" permission was introduced.
     * This permission allows users to configure SEO settings (SeoSetConfig) for all Statamic collections/taxonomies they have access to.
     * Additionally, they get access to edit and configure site defaults (any SeoSet of type "site").
     *
     * If a user had any "edit seo {group} defaults" permissions (except site-level permissions), they should get the "edit seo" permission.
     * If a user had any of the site-level "edit seo" permissions (general, indexing, social_media, analytics, favicons), they should get the "configure seo" permission.
     */
    public function update(): void
    {
        Role::all()->each(function ($role) {
            $permissions = $role->permissions();

            // Site-level permissions that should grant "configure seo"
            $siteLevelPermissions = [
                'edit seo general defaults',
                'edit seo indexing defaults',
                'edit seo social_media defaults',
                'edit seo analytics defaults',
                'edit seo favicons defaults',
            ];

            // Check if role has any site-level permissions
            $hasSiteLevelPermissions = $permissions->contains(function ($permission) use ($siteLevelPermissions) {
                return in_array($permission, $siteLevelPermissions);
            });

            // Add "configure seo" permission if user had any site-level permissions
            if ($hasSiteLevelPermissions) {
                $role->addPermission('configure seo');
            }

            // Check if role has any other "edit seo {group} defaults" permissions (excluding site-level)
            $hasEditSeoDefaults = $permissions->contains(function ($permission) use ($siteLevelPermissions) {
                return preg_match('/^edit seo .+ defaults$/', $permission)
                    && ! in_array($permission, $siteLevelPermissions);
            });

            // Add "edit seo" permission only if user had edit permissions for non-site groups
            if ($hasEditSeoDefaults) {
                $role->addPermission('edit seo');
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
