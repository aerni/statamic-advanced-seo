<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Facades\User;
use Statamic\Policies\Concerns\HasMultisitePolicy;

class SeoVariablesPolicy
{
    use HasMultisitePolicy;

    public function before($user)
    {
        $user = User::fromUser($user);

        if ($user->isSuper() || $user->hasPermission('configure seo')) {
            return true;
        }
    }

    public function index($user, string $type): bool
    {
        return Defaults::enabledInType($type)
            ->filter(fn ($default) => $this->view($user, $default['set']))
            ->isNotEmpty();
    }

    public function view($user, SeoDefaultSet $set): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission("view seo {$set->handle()} defaults")
            && $this->userCanAccessAnySite($user, $set->sites());
    }

    public function edit($user, SeoDefaultSet $set): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission("edit seo {$set->handle()} defaults")
            && $this->userCanAccessAnySite($user, $set->sites());
    }

    public function configure($user, SeoDefaultSet $set): bool
    {
        $user = User::fromUser($user);

        return $user->hasPermission('configure seo')
            && $this->userCanAccessAnySite($user, $set->sites());
    }
}
