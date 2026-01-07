<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Enums\Context;
use Statamic\Support\Traits\FluentlyGetsAndSets;

trait HasDefaultsData
{
    use FluentlyGetsAndSets;

    protected $defaultsData;

    abstract protected function context(): Context;

    public function defaultsData(?DefaultsData $data = null): DefaultsData|self
    {
        return $this->fluentlyGetOrSet('defaultsData')
            ->getter(function ($data) {
                return $data ?? new DefaultsData(
                    type: $this->type(),
                    handle: $this->handle(),
                    locale: method_exists($this, 'locale') ? $this->locale() : null,
                    sites: $this->sites(),
                    context: $this->context(),
                );
            })->args(func_get_args());
    }
}
