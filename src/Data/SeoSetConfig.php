<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Facades\Path;
use Statamic\Facades\Blink;
use Statamic\Facades\Stache;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig as Contract;

class SeoSetConfig implements Contract
{
    use ContainsData;
    use ExistsAsFile;
    use FluentlyGetsAndSets;

    protected bool $enabled = true;

    protected array $origins = [];

    public function __construct(protected readonly string $seoSet)
    {
        $this->data = collect();
    }

    public function seoSet(): SeoSet
    {
        return Blink::once("advanced-seo::{$this->seoSet}", fn () => Seo::find($this->seoSet));
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
        $data = $this->data();

        if ($this->origins()->filter()->isNotEmpty()) {
            $data->put('origins', $this->origins()->all());
        }

        if ($this->type() !== 'site') {
            $data->put('enabled', $this->enabled());
        }

        return $data->all();
    }

    public function editUrl(): string
    {
        return cp_route('advanced-seo.sets.config', [
            'seoSetGroup' => $this->type(),
            'seoSet' => $this->handle(),
        ]);
    }

    // TODO: When the config is saved and there are disabled features like sitemap: false
    // Should we then resave the localizations? And even remove seo_sitemap_enabled from the entries?
    public function save(): self
    {
        SeoConfig::save($this);

        SeoSetConfigSaved::dispatch($this);

        return $this;
    }

    public function delete(): bool
    {
        SeoConfig::delete($this);

        SeoSetConfigDeleted::dispatch($this);

        return true;
    }
}
