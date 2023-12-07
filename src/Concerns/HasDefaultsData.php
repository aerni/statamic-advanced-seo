<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Data\DefaultsData;

trait HasDefaultsData
{
    protected $defaultsData;

    public function defaultsData(?DefaultsData $data = null): DefaultsData|self
    {
        return $this->fluentlyGetOrSet('defaultsData')
            ->getter(function ($data) {
                return $data ?? new DefaultsData(
                    type: $this->type(),
                    handle: $this->handle(),
                    locale: method_exists($this, 'locale') ? $this->locale() : null,
                    sites: $this->sites(),
                );
            })->args(func_get_args());
    }
}
