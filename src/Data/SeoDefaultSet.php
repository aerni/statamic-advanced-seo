<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Globals\GlobalSet as Contract;
use Statamic\Data\ExistsAsFile;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Facades\Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;
use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Blueprints\MarketingBlueprint;

class SeoDefaultSet implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;

    protected string $handle;
    protected string $type;
    protected array $localizations;

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
            ->locale($site);
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

    // TODO: Maybe add a blueprint repository to make this dynamic.
    public function blueprint()
    {
        $blueprints = [
            'general' => GeneralBlueprint::make()->get(),
            'marketing' => MarketingBlueprint::make()->get(),
            'content' => ContentDefaultsBlueprint::make()->get(),
        ];

        return $blueprints[$this->handle];
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
