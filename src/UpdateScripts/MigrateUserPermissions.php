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
      * Old permissions:
      * - view seo {group} defaults
      * - edit seo {group} defaults
      *
      * New permissions:
      * - configure seo
      * - edit seo defaults
      * - edit seo content
      *
      * Migration rules:
      * - "edit seo {group} defaults" (content-level) → "edit seo defaults"
      * - "edit seo {group} defaults" (site-level) → "configure seo"
      * - "view seo {group} defaults" → removed (no replacement)
      * - ALL roles get "edit seo content" to maintain backward compatibility
      *   since users could previously edit SEO on entries/terms without explicit permission control.
      *
      * Permission meanings:
      * - "configure seo": Master permission granting all SEO permissions plus settings access
      * - "edit seo defaults": Allows editing SEO defaults (SeoSetLocalization) for collections/taxonomies
      * - "edit seo content": Allows editing the SEO tab on individual entries/terms
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

            // Add "edit seo defaults" permission only if user had edit permissions for non-site groups
            if ($hasEditSeoDefaults) {
                $role->addPermission('edit seo defaults');
            }

            // Grant "edit seo content" to ALL roles to maintain backward compatibility
            // Users could previously edit SEO on entries/terms without explicit permission control
            $role->addPermission('edit seo content');

            // Remove all old permissions that match the pattern
            $permissions
                ->filter(fn ($permission) => preg_match('/^(view|edit) seo .+ defaults$/', $permission))
                ->each(fn ($oldPermission) => $role->removePermission($oldPermission));

            $role->save();
        });

        $this->console()->info('Migrated user permissions.');
    }
}
