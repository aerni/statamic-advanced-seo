<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Facades\User;
use Statamic\Policies\Concerns\HasMultisitePolicy;

class SeoVariablesPolicy
{
    use HasMultisitePolicy;

    public function before($user)
    {
        $user = User::fromUser($user);

        // Only super users bypass all checks
        if ($user->isSuper()) {
            return true;
        }
    }

    public function index($user, string $type): bool
    {
        $user = User::fromUser($user);

        // Check if user can edit at least one default of this type
        // This applies to ALL users, including those with configure seo
        return Defaults::enabledInType($type)
            ->filter(fn ($default) => $this->edit($user, $default['set']))
            ->isNotEmpty();
    }

    public function edit($user, SeoDefaultSet $set): bool
    {
        $user = User::fromUser($user);

        // Check site access first
        if (! $this->userCanAccessAnySite($user, $set->sites())) {
            return false;
        }

        // Site-level defaults: Only configure seo permission grants access
        if ($set->type() === 'site') {
            return $user->hasPermission('configure seo');
        }

        // Handle deleted collections/taxonomies gracefully
        if ($set->parent() === null) {
            return false;
        }

        // Content-aware permission check for collections/taxonomies
        // Both configure seo users AND users with edit seo defaults need content permissions
        return match ($set->type()) {
            'collections' => $this->canEditCollectionSeoDefaults($user, $set->handle()),
            'taxonomies' => $this->canEditTaxonomySeoDefaults($user, $set->handle()),
            default => false,
        };
    }

    public function configure($user, SeoDefaultSet $set): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission('configure seo')
            && $this->userCanAccessAnySite($user, $set->sites());
    }

    protected function canEditCollectionSeoDefaults($user, string $handle): bool
    {
        $user = User::fromUser($user);

        // User needs either configure seo OR edit seo defaults permission
        $hasBaseSeoPermission = $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults');

        if (! $hasBaseSeoPermission) {
            return false;
        }

        // Check for wildcard collection permission OR specific collection permission
        return $user->hasPermission('configure collections')
            || $user->hasPermission("edit {$handle} entries");
    }

    protected function canEditTaxonomySeoDefaults($user, string $handle): bool
    {
        $user = User::fromUser($user);

        // User needs either configure seo OR edit seo defaults permission
        $hasBaseSeoPermission = $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults');

        if (! $hasBaseSeoPermission) {
            return false;
        }

        // Check for wildcard taxonomy permission OR specific taxonomy permission
        return $user->hasPermission('configure taxonomies')
            || $user->hasPermission("edit {$handle} terms");
    }
}
