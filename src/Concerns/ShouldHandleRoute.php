<?php

namespace Aerni\AdvancedSeo\Concerns;

trait ShouldHandleRoute
{
    protected function isFrontendRoute(): bool
    {
        $currentRoute = request()->route()->getName();
        $allowedRoutes = ['statamic.site', 'advanced-seo.social_images.show'];

        if (! in_array($currentRoute, $allowedRoutes)) {
            return false;
        }

        return true;
    }
}