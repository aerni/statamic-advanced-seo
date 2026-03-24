<?php

namespace Aerni\AdvancedSeo\Gates;

use Statamic\Contracts\Auth\User;
use Statamic\Facades\User as UserFacade;

class SeoContentGate
{
    /**
     * Determine if the user can access the SEO tab and edit content on entries and terms.
     */
    public function editContent(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        if ($user->isSuper()) {
            return true;
        }

        return $user->hasPermission('configure seo')
            || $user->hasPermission('edit seo defaults')
            || $user->hasPermission('edit seo content');
    }
}
