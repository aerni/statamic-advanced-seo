<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Site as Sites;
use Statamic\Facades\User as UserFacade;
use Statamic\Policies\Concerns\HasMultisitePolicy;
use Statamic\Sites\Site;

class SeoConfigurationPolicy
{
    use HasMultisitePolicy;

    public function before(User $user)
    {
        $user = UserFacade::fromUser($user);

        if ($user->isSuper()) {
            return true;
        }
    }

    public function viewAny(User $user, string $type): bool
    {
        $user = UserFacade::fromUser($user);

        return Defaults::enabledInType($type)
            ->contains(function (array $default) use ($user) {
                return $default['set']->sites()
                    ->contains(fn ($site) => $this->edit($user, $default['set'], Sites::get($site)));
            });
    }

    public function edit(User $user, SeoDefaultSet $set, Site $site): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $this->userCanAccessSite($user, $site)) {
            return false;
        }

        return match ($set->type()) {
            'site' => $user->hasPermission('configure seo'),
            'collections' => $this->canEditContentSeoDefaults($user, 'collections', $set->handle()),
            'taxonomies' => $this->canEditContentSeoDefaults($user, 'taxonomies', $set->handle()),
            default => false,
        };
    }

    public function configure(User $user, SeoDefaultSet $set, Site $site): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $this->userCanAccessSite($user, $site)) {
            return false;
        }

        return match ($set->type()) {
            'site' => $user->hasPermission('configure seo'),
            'collections' => $this->canConfigureContentSeoDefaults($user, 'collections', $set->handle()),
            'taxonomies' => $this->canConfigureContentSeoDefaults($user, 'taxonomies', $set->handle()),
            default => false,
        };
    }

    protected function canEditContentSeoDefaults(User $user, string $type, string $handle): bool
    {
        $user = UserFacade::fromUser($user);

        $hasBaseSeoPermission = $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults');

        if (! $hasBaseSeoPermission) {
            return false;
        }

        $itemType = match ($type) {
            'collections' => 'entries',
            'taxonomies' => 'terms',
        };

        // Grant edit permission if they have permission to edit the collection/taxonomy.
        return $user->hasPermission("configure {$type}")
            || $user->hasPermission("edit {$handle} {$itemType}");
    }

    protected function canConfigureContentSeoDefaults(User $user, string $type, string $handle): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $user->hasPermission('configure seo')) {
            return false;
        }

        $itemType = match ($type) {
            'collections' => 'entries',
            'taxonomies' => 'terms',
        };

        // Grant edit permission if they have permission to edit the collection/taxonomy.
        return $user->hasPermission("configure {$type}")
            || $user->hasPermission("edit {$handle} {$itemType}");
    }
}
