<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Contracts\SeoSetGroup as Contract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class SeoSetGroup implements Arrayable, Contract
{
    public function __construct(protected readonly Collection $seoSets)
    {
        //
    }

    public function seoSets(): Collection
    {
        return $this->seoSets;
    }

    public function type(): string
    {
        return $this->seoSets()->first()->type();
    }

    public function title(): string
    {
        return ucfirst($this->type());
    }

    public function indexUrl(): string
    {
        return cp_route('advanced-seo.sets.index', ['seoSetGroup' => $this->type()]);
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
            'indexUrl' => $this->indexUrl(),
            'icon' => $this->icon(),
        ];
    }
}
