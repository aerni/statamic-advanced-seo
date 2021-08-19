<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoDefault
{
    use ContainsData, ExistsAsFile, FluentlyGetsAndSets, TracksQueriedColumns;

    protected $handle;
    protected $type;
    protected $locale;

    public function __construct()
    {
        $this->data = collect();
    }

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

    public function path(): string
    {
        return $this->initialPath ?? $this->buildPath();
    }

    public function buildPath(): string
    {
        return vsprintf('%s/%s/%s%s.yaml', [
            rtrim(Stache::store('seo')->directory(), '/'),
            $this->type(),
            Site::hasMultiple() ? $this->locale(). '/' : '',
            $this->handle(),
        ]);
    }

    public function locale($locale = null)
    {
        return $this
            ->fluentlyGetOrSet('locale')
            ->setter(function ($locale) {
                return $locale instanceof \Statamic\Sites\Site ? $locale->handle() : $locale;
            })
            ->getter(function ($locale) {
                return $locale ?? Site::default()->handle();
            })
            ->args(func_get_args());
    }

    public function save(): bool
    {
        \Aerni\AdvancedSeo\Facades\Seo::save($this);

        return true;
    }

    public function delete(): bool
    {
        \Aerni\AdvancedSeo\Facades\Seo::delete($this);

        return true;
    }

    public function fileData(): array
    {
        return $this->data()->all();
    }
}
