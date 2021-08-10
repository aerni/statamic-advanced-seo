<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Facades\Fieldset;
use Illuminate\Support\Collection;

class TrackersFieldset extends BaseFieldset
{
    protected string $display = 'Trackers';

    protected function sections(): array
    {
        return [
            $this->siteVerification(),
            $this->fathom(),
            $this->cloudflareAnalytics(),
            $this->GoogleTagManager(),
        ];
    }

    protected function siteVerification(): ?Collection
    {
        return config('advanced-seo.trackers.site_verification', true)
            ? Fieldset::find('trackers/site_verification')
            : null;
    }

    protected function fathom(): ?Collection
    {
        return config('advanced-seo.trackers.fathom', true)
            ? Fieldset::find('trackers/fathom')
            : null;
    }

    protected function cloudflareAnalytics(): ?Collection
    {
        return config('advanced-seo.trackers.cloudflare_analytics', true)
            ? Fieldset::find('trackers/cloudflare_analytics')
            : null;
    }

    protected function GoogleTagManager(): ?Collection
    {
        return config('advanced-seo.trackers.google_tag_manager', true)
            ? Fieldset::find('trackers/google_tag_manager')
            : null;
    }
}
