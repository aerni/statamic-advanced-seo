<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Contracts\Fieldset;
use Aerni\AdvancedSeo\Facades\SeoGlobals;
use Illuminate\Support\Collection;

class TrackersFieldset implements Fieldset
{
    public function contents(): ?array
    {
        if ($this->fields()->isEmpty()) {
            return null;
        }

        return [
            'display' => 'Trackers',
            'fields' => $this->fields(),
        ];
    }

    public function fields(): Collection
    {
        return collect()
            ->merge($this->siteVerification())
            ->merge($this->fathom());
    }

    protected function siteVerification(): ?Collection
    {
        if (! config('advanced-seo.trackers.site_verification', true)) {
            return null;
        }

        return SeoGlobals::fieldset('site_verification');
    }

    protected function fathom(): ?Collection
    {
        if (! config('advanced-seo.trackers.fathom', true)) {
            return null;
        }

        return SeoGlobals::fieldset('fathom');
    }
}
