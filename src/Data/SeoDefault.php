<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Blink;

class SeoDefault
{
    public function __construct(
        public readonly string $type,
        public readonly string $handle,
        public readonly string $title,
        public readonly string $blueprint,
        public readonly string $data,
        public readonly string $icon,
    ) {}

    public function set(): SeoDefaultSet
    {
        return Blink::once("seo-default-set-{$this->type}-{$this->handle}", function () {
            return Seo::findOrMake($this->type, $this->handle);
        });
    }

    public function id(): string
    {
        return $this->set()->id();
    }

    public function enabled(): bool
    {
        return $this->set()->enabled();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'type' => $this->type,
            'handle' => $this->handle,
            'title' => $this->title,
            'blueprint' => $this->blueprint,
            'data' => $this->data,
            'enabled' => $this->enabled(),
            'icon' => $this->icon,
            'set' => $this->set(),
        ];
    }
}
