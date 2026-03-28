<?php

namespace Aerni\AdvancedSeo\Services;

use Aerni\AdvancedSeo\Registries\SeoSetRegistry;
use Aerni\AdvancedSeo\SeoSets\SeoData;
use Illuminate\Support\Traits\ForwardsCalls;

class SeoService
{
    use ForwardsCalls;

    public function __construct(protected SeoSetRegistry $registry) {}

    public function data(): SeoData
    {
        return new SeoData;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->registry, $method, $parameters);
    }
}
