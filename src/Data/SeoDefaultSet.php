<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Support\Str;
use Statamic\Facades\Site;
use Statamic\Facades\Blink;
use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Data\ExistsAsFile;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Aerni\AdvancedSeo\Events\SeoDefaultSetSaved;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Statamic\Facades\Collection as CollectionFacade;
use Aerni\AdvancedSeo\Contracts\SeoVariablesRepository;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet as Contract;

class SeoDefaultSet implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use HasDefaultsData;

    protected string $type;

    protected string $handle;

    protected bool $enabled = true;

    protected ?array $origins = [];

    public function id(): string
    {
        return "{$this->type()}::{$this->handle()}";
    }

    public function reference(): string
    {
        return "seo::{$this->id()}";
    }

    public function type($type = null)
    {
        return $this->fluentlyGetOrSet('type')->args(func_get_args());
    }

    public function handle($handle = null)
    {
        return $this->fluentlyGetOrSet('handle')->args(func_get_args());
    }

    public function enabled(?bool $enabled = null): bool|self
    {
        return $this->fluentlyGetOrSet('enabled')
            ->setter(function ($enabled) {
                // Prevent setting enabled on site defaults
                if ($this->type === 'site') {
                    throw new \LogicException('Site defaults cannot be disabled. They are always enabled.');
                }

                return $enabled;
            })
            ->args(func_get_args());
    }

    public function origins($origins = null): Collection|self
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
                // TODO: Should we not set anything if there is only one origin?
                return collect($origins)
                    ->filter(function ($value, $key) {
                        $validValues = $this->sites()->keys();

                        return $validValues->contains($key) && $validValues->contains($value);
                    })->all();
            })
            ->args(func_get_args());
    }

    public function localizations(): Collection
    {
        return Blink::once('seo-defaults-localizations-'.$this->id(), function () {
            return app(SeoVariablesRepository::class)
                ->whereSet($this->type(), $this->handle())
                ->keyBy->locale();
        });
    }

    public function sites(): Collection
    {
        // Only get sites configured on the parent (collection/taxonomy)
        if ($parent = $this->parent()) {
            return $parent->sites()->mapWithKeys(fn ($site) => [$site => Site::get($site)]);
        }

        return Site::all();
    }

    public function availableInSite(string $site): bool
    {
        return $this->sites()->contains($site);
    }

    public function defaultSite(): string
    {
        return $this->sites()->first();
    }

    public function selectedSite(): string
    {
        $selectedSite = Site::selected()->handle();

        return $this->sites()->contains($selectedSite)
            ? $selectedSite
            : $this->defaultSite();
    }

    public function title(): string
    {
        return Str::slugToTitle($this->handle());
    }

    public function path(): string
    {
        return vsprintf('%s/%s.yaml', [
            Stache::store('seo')->store($this->type())->directory(),
            $this->handle(),
        ]);
    }

    public function fileData(): array
    {
        $data = ['origins' => $this->origins];

        // Only save 'enabled' for collections and taxonomies, not for site
        if ($this->type !== 'site') {
            $data['enabled'] = $this->enabled;
        }

        return $data;
    }

    public function makeLocalization(string $site): SeoVariables
    {
        return (new SeoVariables)
            ->seoSet($this->id())
            ->locale($site);
    }


    public function in(string $site): ?SeoVariables
    {
        if (! $this->availableInSite($site)) {
            return null;
        }

        if (! $variables = $this->localizations()->get($site)) {
            $variables = $this->makeLocalization($site);
        }

        return $variables;
    }

    public function inSelectedSite(): ?SeoVariables
    {
        return $this->in(Site::selected()->handle());
    }

    public function inCurrentSite(): ?SeoVariables
    {
        return $this->in(Site::current()->handle());
    }

    public function inDefaultSite(): ?SeoVariables
    {
        return $this->in($this->defaultSite());
    }

    public function existsIn(string $locale): bool
    {
        return $this->in($locale) !== null;
    }

    public function editUrl(): string
    {
        return match ($this->type()) {
            'site' => cp_route('advanced-seo.site.edit', $this->handle()),
            'collections' => cp_route('advanced-seo.collections.edit', $this->handle()),
            'taxonomies' => cp_route('advanced-seo.taxonomies.edit', $this->handle()),
        };
    }

    public function blueprint(): Blueprint
    {
        $blueprint = Defaults::blueprint("{$this->type}::{$this->handle}");

        return resolve($blueprint)->make()
            ->data($this->defaultsData())
            ->get();
    }

    public function parent(): mixed
    {
        return match (true) {
            ($this->type() === 'collections') => CollectionFacade::findByHandle($this->handle()),
            ($this->type() === 'taxonomies') => Taxonomy::findByHandle($this->handle()),
            default => null,
        };
    }

    // TODO: This is just a feature toggle for things like favicons.
    // The enabled state for collections/taxonomies is saved in $this->data.
    public function isEnabled(): bool
    {
        return Defaults::isEnabled("{$this->type()}::{$this->handle()}");
    }

    public function save(): self
    {
        \Aerni\AdvancedSeo\Facades\Seo::save($this);

        SeoDefaultSetSaved::dispatch($this);

        $this->saveOrDeleteLocalizations();

        return $this;
    }

    protected function saveOrDeleteLocalizations(): void
    {
        $localizations = $this->localizations();

        // Delete all localizations if the set is disabled.
        if (! $this->enabled()) {
            $localizations->each->delete();
            RemoveSeoValues::handle($this->parent());

            return;
        }

        // Save localizations that don't exist on file yet.
        $this->sites()
            ->reject(fn ($site, $handle) => $localizations->has($handle))
            ->each(fn ($site) => $this->makeLocalization($site)->save());

        // Delete all localizations that shouldn't exist based on the configured sites.
        $localizations
            ->filter(fn ($localization) => ! $this->sites()->contains($localization->locale()))
            ->each->delete();
    }

    public function delete(): bool
    {
        \Aerni\AdvancedSeo\Facades\Seo::delete($this);

        $this->localizations()->each->delete();

        return true;
    }

    public static function __callStatic($method, $parameters)
    {
        return \Aerni\AdvancedSeo\Facades\Seo::{$method}(...$parameters);
    }
}
