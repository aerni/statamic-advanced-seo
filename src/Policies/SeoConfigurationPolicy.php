<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
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

        return Seo::whereType($type)
            ->contains(function (SeoSet $default) use ($user) {
                return $default->sites()
                    ->contains(fn ($site) => $this->edit($user, $default, $site));
            });
    }

    public function edit(User $user, SeoSet $default, Site $site): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $this->userCanAccessSite($user, $site)) {
            return false;
        }

        return match ($default->type()) {
            'site' => $user->hasPermission('configure seo'),
            'collections' => $this->canEditContentSeoSets($user, 'collections', $default->handle()),
            'taxonomies' => $this->canEditContentSeoSets($user, 'taxonomies', $default->handle()),
            default => false,
        };
    }

    public function configure(User $user, SeoSet $default): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $this->userCanAccessSite($user, Sites::selected())) {
            return false;
        }

        return match ($default->type()) {
            'site' => $user->hasPermission('configure seo'),
            'collections' => $this->canConfigureContentSeoSets($user, 'collections', $default->handle()),
            'taxonomies' => $this->canConfigureContentSeoSets($user, 'taxonomies', $default->handle()),
            default => false,
        };
    }

    protected function canEditContentSeoSets(User $user, string $type, string $handle): bool
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

    protected function canConfigureContentSeoSets(User $user, string $type, string $handle): bool
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
