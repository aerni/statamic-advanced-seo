<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\User as UserFacade;

class SeoVariablesPolicy
{
    public function index(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $allTypes = Defaults::enabled()->map->handle;

        $permissions = $allTypes->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function siteDefaultsIndex(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $permissions = Defaults::enabledInGroup('site')->map->handle->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function contentDefaultsIndex(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $permissions = Defaults::enabledInGroup('content')->map->handle->filter(function ($type) use ($user) {
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
