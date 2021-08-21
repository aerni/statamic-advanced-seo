<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoVariables
{
    use ContainsData, ExistsAsFile, FluentlyGetsAndSets, HasOrigin;

    protected $set;
    protected $locale;

    public function __construct()
    {
        $this->data = collect();
    }

    public function seoSet($set = null)
    {
        return $this->fluentlyGetOrSet('set')->args(func_get_args());
    }

    public function locale($locale = null)
    {
        return $this->fluentlyGetOrSet('locale')->args(func_get_args());
    }

    public function id()
    {
        return $this->seoSet()->id();
    }

    public function handle()
    {
        return $this->seoSet()->handle();
    }

    public function type()
    {
        return $this->seoSet()->type();
    }

    public function path()
    {
        return vsprintf('%s/%s/%s%s.yaml', [
            rtrim(Stache::store('seo')->directory(), '/'),
            $this->type(),
            Site::hasMultiple() ? $this->locale(). '/' : '',
            $this->handle(),
        ]);
    }

    public function save()
    {
        $this
            ->seoSet()
            ->addLocalization($this)
            ->save();

        return $this;
    }

    public function site()
    {
        return Site::get($this->locale());
    }

    public function fileData()
    {
        return array_merge([
            'origin' => $this->hasOrigin() ? $this->origin()->locale() : null,
        ], $this->data()->all());
    }

    protected function getOriginByString($origin)
    {
        return $this->seoSet()->in($origin);
    }
}
