<?php

namespace Aerni\AdvancedSeo\Policies;

use Statamic\Facades\User as UserFacade;
use Statamic\Contracts\Auth\User;

class SeoVariablesPolicy
{
    protected $siteDefaults = ['general', 'marketing'];
    protected $contentDefaults = ['collection', 'taxonomy'];

    public function index(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $allTypes = collect($this->siteDefaults)->merge($this->contentDefaults);

        $permissions = $allTypes->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function siteDefaultsIndex(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $permissions = collect($this->siteDefaults)->filter(function ($type) use ($user) {
            return $this->view($user, $type);
        });

        return $permissions->isNotEmpty();
    }

    public function contentDefaultsIndex(User $user): bool
    {
        $user = UserFacade::fromUser($user);

        $permissions = collect($this->contentDefaults)->filter(function ($type) use ($user) {
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
