<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Statamic\Facades\User;
use Statamic\Policies\Concerns\HasMultisitePolicy;

class SeoSetPolicy
{
    use HasMultisitePolicy;

    public function before($user)
    {
        if (! AdvancedSeo::pro()) {
            return true;
        }

        $user = User::fromUser($user);

        if ($user->isSuper()) {
            return true;
        }
    }

    public function viewAny($user, SeoSetGroup $group): bool
    {
        $user = User::fromUser($user);

        return $group->seoSets()->contains(function (SeoSet $seoSet) use ($user) {
            return $seoSet->localizations()->contains(fn (SeoSetLocalization $localization) => $this->edit($user, $localization));
        });
    }

    public function edit($user, SeoSetLocalization $localization): bool
    {
        $user = User::fromUser($user);

        if (! $this->userCanAccessSite($user, $localization->site())) {
            return false;
        }

        if ($localization->type() === 'site') {
            return $user->hasPermission('configure seo');
        }

        $canEditLocalization = $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults');

        return $canEditLocalization && $this->canEditStatamicContent($user, $localization->type(), $localization->handle());
    }

    public function configure($user, SeoSet $seoSet): bool
    {
        $user = User::fromUser($user);

        if (! $user->hasPermission('configure seo')) {
            return false;
        }

        return $seoSet->type() === 'site'
            || $this->canEditStatamicContent($user, $seoSet->type(), $seoSet->handle());
    }

    protected function canEditStatamicContent($user, string $type, string $handle): bool
    {
        $itemType = match ($type) {
            'collections' => 'entries',
            'taxonomies' => 'terms',
        };

        return $user->hasPermission("configure {$type}")
            || $user->hasPermission("edit {$handle} {$itemType}");
    }
}
