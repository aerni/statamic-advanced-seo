<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Contracts\Globals\GlobalSet as Contract;
use Statamic\Data\ExistsAsFile;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoDefaultSet implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;

    protected string $handle;
    protected string $type;
    protected array $localizations;

    public function defaultData(): Collection
    {
        return Defaults::data("{$this->type}::{$this->handle}");
    }

    public function id(): string
    {
        return $this->handle();
    }

    public function handle($handle = null)
    {
        return $this->fluentlyGetOrSet('handle')->args(func_get_args());
    }

    public function type($type = null)
    {
        return $this->fluentlyGetOrSet('type')->args(func_get_args());
    }

    public function localizations(): Collection
    {
        return collect($this->localizations);
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
        $data = [
            'title' => $this->title(),
        ];

        if (! Site::hasMultiple()) {
            $data['data'] = Arr::removeNullValues(
                $this->in(Site::default()->handle())->data()->all()
            );
        }

        return $data;
    }

    public function makeLocalization(string $site): SeoVariables
    {
        return (new SeoVariables)
            ->seoSet($this)
            ->data($this->defaultData())
            ->locale($site);
    }

    public function createLocalizations(Collection $sites): self
    {
        return $this->ensureLocalizations($sites)->save();
    }

    public function createOrDeleteLocalizations(Collection $sites): self
    {
        return $this
            ->ensureLocalizations($sites)
            ->removeLocalizations($sites)
            ->save();
    }

    public function ensureLocalizations(Collection $sites): self
    {
        // We only want to handle sites that are configured in Statamic's sites config.
        $sites = $sites->intersect(Site::all()->keys());

        // Make a localization for each site if it doesn't already exist.
        $sites->each(function ($site) {
            $this->in($site) ?? $this->addLocalization($this->makeLocalization($site));
        });

        // Determine the origin for each localization based on the provided sites.
        $this->localizations()->each->determineOrigin($sites);

        return $this;
    }

    public function removeLocalizations(Collection $sites): self
    {
        $localizationsToDelete = $this->localizations()->map->locale()->diff($sites);

        $localizationsToDelete->each(function ($localization) {
            $this->removeLocalization($this->localizations()->get($localization));
        });

        return $this;
    }

    public function addLocalization(SeoVariables $localization): self
    {
        $localization->seoSet($this);

        $this->localizations[$localization->locale()] = $localization;

        return $this;
    }

    public function removeLocalization(SeoVariables $localization): self
    {
        unset($this->localizations[$localization->locale()]);

        return $this;
    }

    public function in(string $locale): ?SeoVariables
    {
        return $this->localizations[$locale] ?? null;
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
        return $this->in(Site::default()->handle());
    }

    public function existsIn(string $locale): bool
    {
        return $this->in($locale) !== null;
    }

    public function blueprint(): Blueprint
    {
        return resolve(Defaults::blueprint("{$this->type}::{$this->handle}"))->make()
            ->data(collect([
                'type' => $this->type,
                'handle' => $this->handle,
            ]))->get();
    }

    public function save(): self
    {
        \Aerni\AdvancedSeo\Facades\Seo::save($this);

        return $this;
    }

    public function delete(): bool
    {
        \Aerni\AdvancedSeo\Facades\Seo::delete($this);

        return true;
    }

    public static function __callStatic($method, $parameters)
    {
        return \Aerni\AdvancedSeo\Facades\Seo::{$method}(...$parameters);
    }
}
