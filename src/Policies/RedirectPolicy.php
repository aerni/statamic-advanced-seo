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

    public function viewAny($user): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission('view redirects')
            && $this->userCanAccessSite($user, Site::selected());
    }

    public function create($user): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission('create redirects')
            && $this->userCanAccessSite($user, Site::selected());
    }

    public function edit($user, Redirect $redirect): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission('edit redirects')
            && $this->userCanAccessSite($user, Site::get($redirect->site()));
    }

    public function delete($user, Redirect $redirect): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission('delete redirects')
            && $this->userCanAccessSite($user, Site::get($redirect->site()));
    }
}
