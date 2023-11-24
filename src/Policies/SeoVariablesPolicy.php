<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Facades\User;

class SeoVariablesPolicy
{
    public function index($user, string $type): bool
    {
        return Defaults::enabledInType($type)->map->handle
            ->filter(fn ($type) => $this->view($user, $type))
            ->isNotEmpty();
    }

    public function view($user, string $type): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission("view seo {$type} defaults");
    }

    public function edit($user, string $type): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission("edit seo {$type} defaults");
    }
}
