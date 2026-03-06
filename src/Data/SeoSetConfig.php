<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Actions\HasCircularOrigins;
use Aerni\AdvancedSeo\Blueprints\ContentSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Blueprints\SiteSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Concerns\HasDefaultValues;
use Aerni\AdvancedSeo\Concerns\HasSeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig as Contract;
use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;
use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Illuminate\Support\Collection;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Path;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoSetConfig implements Contract
{
    use ContainsData;
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use HasDefaultValues;
    use HasOrigin;
    use HasSeoSet;

    protected bool $enabled = true;

    protected bool $editable = true;

    protected array $origins = [];

    public function __construct()
    {
        $this->data = collect();
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
            ->getter(fn ($enabled) => $this->type() === 'site' ? true : $enabled)
            ->args(func_get_args());
    }

    public function editable(?bool $editable = null): bool|self
    {
        return $this->fluentlyGetOrSet('editable')->args(func_get_args());
    }

    public function origins(?array $origins = null): Collection|self
    {
        return $this
            ->fluentlyGetOrSet('origins')
            ->getter(function ($origins) {
                if ($this->sites()->count() === 1) {
                    return collect($origins);
                }

                return $this->sites()->map(fn ($site, $key) => $origins[$key] ?? null);
            })
            ->setter(function ($origins) {
                $validSites = $this->sites()->keys();

                $filtered = collect($origins)->filter(function ($value, $key) use ($validSites) {
                    // Only keep entries where the key is a valid site handle
                    if (! $validSites->contains($key)) {
                        return false;
                    }

                    // Keep null values (sites without origins)
                    if ($value === null) {
                        return true;
                    }

                    // Keep valid origin site handles
                    return $validSites->contains($value);
                })->all();

                if (HasCircularOrigins::handle($filtered)) {
                    throw new \InvalidArgumentException('Circular site origin dependencies are not allowed.');
                }

                return $filtered;
            })
            ->args(func_get_args());
    }

    public function sites(): Collection
    {
        return $this->seoSet()->sites();
    }

    public function blueprint(): Blueprint
    {
        $class = match ($this->type()) {
            'site' => SiteSeoSetConfigBlueprint::class,
            'collections', 'taxonomies' => ContentSeoSetConfigBlueprint::class,
        };

        return $class::resolve($this);
    }

    protected function getOriginByString($origin): null
    {
        return null;
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
        $data = collect();

        if ($this->type() !== 'site') {
            $data->put('enabled', $this->enabled());
            $data->put('editable', $this->editable());
        }

        if ($this->origins()->filter()->isNotEmpty()) {
            $data->put('origins', $this->origins()->all());
        }

        return $data->merge($this->data())->all();
    }

    public function editUrl(): string
    {
        return cp_route('advanced-seo.sets.config', [
            'seoSetGroup' => $this->type(),
            'seoSet' => $this->handle(),
        ]);
    }

    public function save(): self
    {
        SeoConfig::save($this);

        SeoSetConfigSaved::dispatch($this);

        $this->seoSet()->flushBlink();

        return $this;
    }

    public function delete(): bool
    {
        SeoConfig::delete($this);

        SeoSetConfigDeleted::dispatch($this);

        $this->seoSet()->flushBlink();

        return true;
    }
}
