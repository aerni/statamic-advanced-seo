<?php

namespace Aerni\AdvancedSeo\Policies;

use Statamic\Contracts\Auth\User;
use Aerni\AdvancedSeo\Facades\Defaults;
use Statamic\Facades\User as UserFacade;

class SeoVariablesPolicy
{
    public function index(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $allTypes = Defaults::all()->map->handle;

        $permissions = $allTypes->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function siteDefaultsIndex(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $permissions = Defaults::site()->map->handle->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function contentDefaultsIndex(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $permissions = Defaults::content()->map->handle->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function view(User $user, string $set): bool
    {
        $user = UserFacade::fromUser($user);

        return $user->hasPermission("view {$set} defaults");
    }

    public function edit(User $user, string $set): bool
    {
        $user = UserFacade::fromUser($user);

        return $user->hasPermission("edit {$set} defaults");
    }
}
