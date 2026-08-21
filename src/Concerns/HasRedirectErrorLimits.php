<?php

namespace Aerni\AdvancedSeo\Concerns;

trait HasRedirectErrorLimits
{
    public function maxRecords(): ?int
    {
        $value = config('advanced-seo.redirects.errors.max_records', 1000);

        if ($value === false) {
            return null;
        }

        return max(1, (int) $value);
    }

    public function purgeAfterDays(): ?int
    {
        $value = config('advanced-seo.redirects.errors.purge_after_days', 30);

        if ($value === false) {
            return null;
        }

        return max(1, (int) $value);
    }
}
