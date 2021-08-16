<?php

namespace Aerni\AdvancedSeo\Storage;

use Illuminate\Support\Collection;
use Statamic\Facades\File;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;

class GlobalsStorage
{
    protected string $siteHandle;

    public function get(string $basename): Collection
    {
        return collect(YAML::file($this->path($basename))->parse());
    }

    public function store(string $basename, array $data): void
    {
        File::put($this->path($basename), YAML::dump($data));
    }

    public function path(string $basename): string
    {
        return Site::hasMultiple()
            ? base_path("content/seo/{$this->siteHandle}/{$basename}.yaml")
            : base_path("content/seo/{$basename}.yaml");
    }

    public function in(string $handle): self
    {
        $this->siteHandle = $handle;

        return $this;
    }

    public function inSelectedSite(): self
    {
        return $this->in(Site::selected()->handle());
    }

    public function inCurrentSite(): self
    {
        return $this->in(Site::current()->handle());
    }

    public function inDefaultSite(): self
    {
        return $this->in(Site::default()->handle());
    }
}
