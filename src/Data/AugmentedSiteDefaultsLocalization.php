<?php

namespace Aerni\AdvancedSeo\Data;

class AugmentedSiteDefaultsLocalization extends AugmentedLocalization
{
    /**
     * Augment site_name with a fallback to the Statamic site name.
     */
    public function siteName(): string
    {
        return $this->data->value('site_name') ?? $this->data->site()->name();
    }
}
