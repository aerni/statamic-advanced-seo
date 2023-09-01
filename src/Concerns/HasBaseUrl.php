<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Support\Traits\FluentlyGetsAndSets;

trait HasBaseUrl
{
    use FluentlyGetsAndSets;

    protected ?string $baseUrl = null;

    public function baseUrl(string $baseUrl = null): self|string|null
    {
        return $this->fluentlyGetOrSet('baseUrl')->args(func_get_args());
    }
}
