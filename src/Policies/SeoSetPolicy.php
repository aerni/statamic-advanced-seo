<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Contracts\SeoSetGroup;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Site as Sites;
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
        // TODO: Should this just return $this->configure() first and then go on with the other edit checks?
        // Only drawback would be that we are calling methods like canEditStatamicContent() multiple times.
        $user = UserFacade::fromUser($user);

        if (! $this->userCanAccessSite($user, $localization->site())) {
            return false;
        }

        return match ($localization->type()) {
            'site' => $user->hasPermission('configure seo'),
            'collections' => $this->canEditContentLocalization($user, $localization),
            'taxonomies' => $this->canEditContentLocalization($user, $localization),
            default => false,
        };
    }

    // TODO: Should this accept an SeoSetConfig instead?
    public function configure(User $user, SeoSet $seoSet): bool
    {
        $user = UserFacade::fromUser($user);

        // TODO: Do we even need this permission check here? Sets have no localizations.
        if (! $this->userCanAccessSite($user, Sites::selected())) {
            return false;
        }

        return match ($seoSet->type()) {
            'site' => $user->hasPermission('configure seo'),
            'collections' => $this->canConfigureContentSeoSets($user, $seoSet),
            'taxonomies' => $this->canConfigureContentSeoSets($user, $seoSet),
            default => false,
        };
    }

    protected function canEditContentLocalization(User $user, SeoSetLocalization $localization): bool
    {
        $hasBaseSeoPermission = $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults');

        if (! $hasBaseSeoPermission) {
            return false;
        }

        return $this->canEditStatamicContent($user, $localization->type(), $localization->handle());
    }

    protected function canConfigureContentSeoSets(User $user, SeoSet $seoSet): bool
    {
        if (! $user->hasPermission('configure seo')) {
            return false;
        }

        return $this->canEditStatamicContent($user, $seoSet->type(), $seoSet->handle());
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
