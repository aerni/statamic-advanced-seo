<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\User as UserFacade;

class SeoVariablesPolicy
{
    public function index(User $user, string $type): bool
    {
        return Defaults::enabledInType($type)->map->handle
            ->filter(fn ($type) => $this->view($user, $type))
            ->isNotEmpty();
    }

    public function view(User $user, string $type): bool
    {
        $user = UserFacade::fromUser($user);

        return $user->hasPermission("view seo {$type} defaults");
    }

    public function edit(User $user, string $type): bool
    {
        $user = UserFacade::fromUser($user);

        return $user->hasPermission("edit seo {$type} defaults");
    }
}
