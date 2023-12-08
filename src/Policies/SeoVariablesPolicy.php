<?php

namespace Aerni\AdvancedSeo\Policies;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class SeoVariablesPolicy
{
    public function index($user, string $type): bool
    {
        return Defaults::enabledInType($type)
            ->filter(fn ($default) => $this->view($user, $default['set']))
            ->isNotEmpty();
    }

    public function view($user, SeoDefaultSet $set): bool
    {
        if (! $this->userCanAccessSite($set)) {
            return false;
        }

        return User::fromUser($user)
            ->hasPermission("view seo {$set->handle()} defaults");
    }

    public function edit($user, SeoDefaultSet $set): bool
    {
        if (! $this->userCanAccessSite($set)) {
            return false;
        }

        return User::fromUser($user)
            ->hasPermission("edit seo {$set->handle()} defaults");
    }

    protected function userCanAccessSite(SeoDefaultSet $set): bool
    {
        return $set->sites()
            ->intersect(Site::authorized()) // Removes sites the user is not authorized for.
            ->contains(request()->site ?? Site::selected()->handle()); // Checks if the user is authorized for the requested site.
    }
}
