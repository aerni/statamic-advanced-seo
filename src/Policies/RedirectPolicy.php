<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Facades\User;

class RedirectPolicy
{
    public function viewAny($user): bool
    {
        return User::fromUser($user)->hasPermission('view redirects');
    }

    public function view($user, Redirect $redirect): bool
    {
        return $this->viewAny($user);
    }

    public function create($user): bool
    {
        return User::fromUser($user)->hasPermission('create redirects');
    }

    public function update($user, Redirect $redirect): bool
    {
        return User::fromUser($user)->hasPermission('edit redirects');
    }

    public function delete($user, Redirect $redirect): bool
    {
        return User::fromUser($user)->hasPermission('delete redirects');
    }
}
