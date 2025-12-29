<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Contracts\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig as Contract;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Illuminate\Support\Collection;
use Statamic\Data\ExistsAsFile;
use Statamic\Facades\Blink;
use Statamic\Facades\Path;
use Statamic\Facades\Stache;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoSetConfig implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;

    protected bool $enabled = true;

    protected array $origins = [];

    public function __construct(protected readonly string $seoSet)
    {
        //
    }

    public function seoSet(): SeoSet
    {
        return Blink::once("seo::{$this->seoSet}", fn () => Seo::find($this->seoSet));
    }

    public function id(): string
    {
        return "{$this->seoSet()->id()}";
    }

    public function type(): string
    {
        return $this->seoSet()->type();
    }

    public function handle(): string
    {
        return $this->seoSet()->handle();
    }

    public function enabled(?bool $enabled = null): bool|self
    {
        return $this->fluentlyGetOrSet('enabled')
            ->setter(function ($enabled) {
                if ($this->type() === 'site') {
                    throw new \LogicException('Site defaults cannot be disabled. They are always enabled.');
                }

                return $enabled;
            })
            ->args(func_get_args());
    }

    public function origins(?array $origins = null): Collection|self
    {
        return $this
            ->fluentlyGetOrSet('origins')
            ->getter(function ($origins) {
                if (empty($origins) && $this->sites()->count() > 1) {
                    return $this->sites()->map(fn ($site) => null);
                }

                return collect($origins);
            })
            ->setter(function ($origins) {
                return collect($origins)
                    ->filter(function ($value, $key) {
                        $validValues = $this->sites()->keys();

                        return $validValues->contains($key) && $validValues->contains($value);
                    })->all();
            })
            ->args(func_get_args());
    }

    public function sites(): Collection
    {
        return $this->seoSet()->sites();
    }

    public function path(): string
    {
        return Path::assemble(
            Stache::store('seo-set-configs')->directory(),
            $this->type(),
            "{$this->handle()}.yaml"
        );
    }

    public function fileData(): array
    {
        $data = [];

        if ($this->origins()->filter()->isNotEmpty()) {
            $data['origins'] = $this->origins()->all();
        }

        if ($this->type() !== 'site') {
            $data['enabled'] = $this->enabled();
        }

        return $data;
    }

    public function editUrl(): string
    {
        return cp_route("advanced-seo.{$this->type()}.edit", $this->handle());
    }

    public function save(): self
    {
        SeoConfig::save($this);

        return $this;
    }

    public function delete(): bool
    {
        SeoConfig::delete($this);

        return true;
    }
}
