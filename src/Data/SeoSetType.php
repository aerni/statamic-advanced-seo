<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Contracts\SeoSetType as Contract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

// TODO: Maybe call this SeoSetTypeGroup
class SeoSetType implements Arrayable, Contract
{
    public function __construct(public readonly Collection $sets)
    {
        //
    }

    // TODO: Maybe call this "handle" to be persistent?
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
        return "advanced-seo.{$this->type()}.index";
    }

    public function indexUrl(): string
    {
        return cp_route($this->route());
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
            'indexUrl' => $this->indexUrl(),
            'icon' => $this->icon(),
        ];
    }
}
