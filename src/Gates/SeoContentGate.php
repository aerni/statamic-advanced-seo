<?php

namespace Aerni\AdvancedSeo\Gates;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\User as UserFacade;

class SeoContentGate
{
    /**
     * Determine if the user can access the SEO tab and edit content on entries and terms.
     */
    public function editContent(User $user, SeoSet $seoSet): bool
    {
        if (! AdvancedSeo::pro()) {
            return true;
        }

        // Non-editable set locks SEO editing for every user, regardless of role.
        if (! $seoSet->editable()) {
            return false;
        }

        $user = UserFacade::fromUser($user);

        if ($user->isSuper()) {
            return true;
        }

        return $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults')
            || $user->hasPermission('edit seo content');
    }
}
