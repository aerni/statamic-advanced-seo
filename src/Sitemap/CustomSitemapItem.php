<?php

namespace Aerni\AdvancedSeo\Sitemap;

class CustomSitemapItem
{
    public function __construct(
        public string $loc,
        public ?string $lastmod = null,
        public ?string $changefreq = null,
        public ?string $priority = null,
    ) {
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (empty($arguments)) {
            return $this->$name;
        }

        $this->$name = $arguments[0];

        return $this;
    }
}
