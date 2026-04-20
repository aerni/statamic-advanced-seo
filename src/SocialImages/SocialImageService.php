<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Aerni\AdvancedSeo\Registries\SocialImageRegistry;
use Aerni\AdvancedSeo\Registries\SocialImageThemeRegistry;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin SocialImageRegistry
 */
class SocialImageService
{
    use ForwardsCalls;

    public function __construct(
        protected SocialImageRegistry $registry,
        protected SocialImageThemeRegistry $themeRegistry,
    ) {}

    public function themes(): SocialImageThemeRegistry
    {
        return $this->themeRegistry;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->registry, $method, $parameters);
    }
}
