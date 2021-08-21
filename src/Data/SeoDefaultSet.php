<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Support\Arr;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Data\ExistsAsFile;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Statamic\Contracts\Globals\GlobalSet as Contract;

class SeoDefaultSet implements Contract
{
    use ExistsAsFile, FluentlyGetsAndSets;

    protected $handle;
    protected $type;
    protected $localizations;

    public function id()
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

    public function localizations()
    {
        return collect($this->localizations);
    }

    public function path()
    {
        return vsprintf('%s/%s/%s.yaml', [
            rtrim(Stache::store('seo')->directory(), '/'),
            $this->type(),
            $this->handle(),
        ]);
    }

    public function fileData()
    {
        if (! Site::hasMultiple()) {
            return Arr::removeNullValues(
                $this->in(Site::default()->handle())->data()->all()
            );
        }
    }

    public function makeLocalization($site)
    {
        return (new SeoVariables)
            ->seoSet($this)
            ->locale($site);
    }

    public function addLocalization($localization)
    {
        $localization->seoSet($this);

        $this->localizations[$localization->locale()] = $localization;

        return $this;
    }

    public function removeLocalization($localization)
    {
        unset($this->localizations[$localization->locale()]);

        return $this;
    }

    public function in($locale)
    {
        return $this->localizations[$locale] ?? null;
    }

    public function inSelectedSite()
    {
        return $this->in(Site::selected()->handle());
    }

    public function inCurrentSite()
    {
        return $this->in(Site::current()->handle());
    }

    public function inDefaultSite()
    {
        return $this->in(Site::default()->handle());
    }

    public function existsIn($locale)
    {
        return $this->in($locale) !== null;
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
}
