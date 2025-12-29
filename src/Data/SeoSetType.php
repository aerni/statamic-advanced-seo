<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Contracts\SeoSetType as Contract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class SeoSetType implements Arrayable, Contract
{
    public function __construct(public readonly Collection $sets)
    {
        //
    }

    public function type(): string
    {
        return $this->sets->first()->type();
    }

    public function title(): string
    {
        return ucfirst($this->type());
    }

    public function route(): string
    {
        return cp_route("advanced-seo.{$this->type()}.index");
    }

    public function icon(): string
    {
        return match ($this->type()) {
            'site' => 'web',
            'collections' => 'collections',
            'taxonomies' => 'taxonomies',
            default => 'folder-generic',
        };
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'route' => $this->route(),
            'icon' => $this->icon(),
        ];
    }
}
