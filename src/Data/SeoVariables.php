<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Facades\Site;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Stache;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Contracts\Data\Localization;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoVariables implements Localization
{
    use ContainsData, ExistsAsFile, FluentlyGetsAndSets, HasOrigin;

    protected SeoDefaultSet $set;
    protected string $locale;

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

    public function id(): string
    {
        return $this->seoSet()->id();
    }

    public function handle(): string
    {
        return $this->seoSet()->handle();
    }

    public function type(): string
    {
        return $this->seoSet()->type();
    }

    public function path(): string
    {
        return vsprintf('%s/%s%s.yaml', [
            Stache::store('seo')->store($this->type())->directory(),
            Site::hasMultiple() ? $this->locale(). '/' : '',
            $this->handle(),
        ]);
    }

    public function save(): self
    {
        $this
            ->seoSet()
            ->addLocalization($this)
            ->save();

        return $this;
    }

    public function delete(): self
    {
        $this
            ->seoSet()
            ->removeLocalization($this)
            ->save();

        return $this;
    }

    public function fileData(): array
    {
        return array_merge([
            'origin' => $this->hasOrigin() ? $this->origin()->locale() : null,
        ], $this->data()->all());
    }

    protected function getOriginByString(string $origin): ?self
    {
        return $this->seoSet()->in($origin);
    }
}
