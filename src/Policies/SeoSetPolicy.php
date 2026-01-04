<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetGroup;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\User as UserFacade;
use Statamic\Policies\Concerns\HasMultisitePolicy;

class SeoSetPolicy
{
    use HasMultisitePolicy;

    public function before(User $user)
    {
        $user = UserFacade::fromUser($user);

        if ($user->isSuper()) {
            return true;
        }
    }

    public function viewAny(User $user, SeoSetGroup $group): bool
    {
        $user = UserFacade::fromUser($user);

        return $group->seoSets()->contains(function (SeoSet $seoSet) use ($user) {
            return $seoSet->localizations()->contains(fn (SeoSetLocalization $localization) => $this->edit($user, $localization));
        });
    }

    public function edit(User $user, SeoSetLocalization $localization): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $this->userCanAccessSite($user, $localization->site())) {
            return false;
        }

        if ($localization->type() === 'site') {
            return $user->hasPermission('configure seo');
        }

        $canEditLocalization = $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo');

        return $canEditLocalization && $this->canEditStatamicContent($user, $localization->type(), $localization->handle());
    }

    public function configure(User $user, SeoSet $seoSet): bool
    {
        $user = UserFacade::fromUser($user);

        if (! $user->hasPermission('configure seo')) {
            return false;
        }

        return $seoSet->type() === 'site'
            || $this->canEditStatamicContent($user, $seoSet->type(), $seoSet->handle());
    }

    protected function canEditStatamicContent(User $user, string $type, string $handle): bool
    {
        $itemType = match ($type) {
            'collections' => 'entries',
            'taxonomies' => 'terms',
        };

        return $user->hasPermission("configure {$type}")
            || $user->hasPermission("edit {$handle} {$itemType}");
    }
}
