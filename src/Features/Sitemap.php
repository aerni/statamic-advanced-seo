<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;

class Sitemap
{
    public static function enabled(DefaultsData $data): bool
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return false;
        }

        // TODO: Is this really required? If the set is disabled, there won't be a feature check on the blueprint
        // as the blueprint won't be extended anyway
        // Check if the collection/taxonomy is set to be disabled.
        if (! $data->set()->enabled()) {
            return false;
        }

        $config = Seo::find('site::indexing')->in($data->locale);

        // If there is no config, the sitemap should be indexable.
        if (is_null($config)) {
            return true;
        }

        // TODO: This needs to be changed when we move the sitemap settings to the seo set
        // Check if the collection/taxonomy is set to be excluded from the sitemap
        $excluded = $config->value("excluded_{$data->type}") ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($data->handle, $excluded);
    }
}
