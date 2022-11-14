<?php

namespace Aerni\AdvancedSeo\Conditions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;

class ShowSitemapFields
{
    public static function handle(DefaultsData $data): bool
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return false;
        }

        $disabled = config("advanced-seo.disabled.{$data->type}", []);

        // Check if the collection/taxonomy is set to be disabled globally.
        if (in_array($data->handle, $disabled)) {
            return false;
        }

        $config = Seo::find('site', 'indexing')?->in($data->locale);

        // If there is no config, the sitemap should be indexable.
        if (is_null($config)) {
            return true;
        }

        // If we have a global noindex, the sitemap shouldn't be indexable.
        if ($config->value('noindex')) {
            return false;
        }

        // Check if the collection/taxonomy is set to be excluded from the sitemap
        $excluded = $config->value("excluded_{$data->type}") ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($data->handle, $excluded);
    }
}
