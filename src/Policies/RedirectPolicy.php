<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Policies\Concerns\HasMultisitePolicy;

class RedirectPolicy
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

    public function manage($user, $redirect = null): bool
    {
        $user = User::fromUser($user);

        $site = $redirect instanceof Redirect
            ? Site::get($redirect->site())
            : Site::selected();

        return $user->hasPermission('manage redirects')
            && $this->userCanAccessSite($user, $site);
    }
}
