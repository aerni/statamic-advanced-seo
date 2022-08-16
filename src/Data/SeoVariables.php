<?php

namespace Aerni\AdvancedSeo\Data;

use Illuminate\Support\Collection;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Augmented;
use Statamic\Contracts\Data\Localization;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\GraphQL\ResolvesValues;
use Statamic\Support\Arr;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoVariables implements Localization, Augmentable
{
    use ContainsData;
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use HasAugmentedInstance;
    use HasOrigin;
    use ResolvesValues;

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

    public function title(): string
    {
        return $this->seoSet()->title();
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

    public function editUrl()
    {
        return [
            'site' => $this->cpUrl('advanced-seo.site.edit'),
            'collections' => $this->cpUrl('advanced-seo.collections.edit'),
            'taxonomies' => $this->cpUrl('advanced-seo.taxonomies.edit'),
        ][$this->type()];
    }

    public function updateUrl()
    {
        return [
            'site' => $this->cpUrl('advanced-seo.site.update'),
            'collections' => $this->cpUrl('advanced-seo.collections.update'),
            'taxonomies' => $this->cpUrl('advanced-seo.taxonomies.update'),
        ][$this->type()];
    }

    protected function cpUrl($route)
    {
        $params = [$this->handle()];

        if (Site::hasMultiple()) {
            $params['site'] = $this->locale();
        }

        return cp_route($route, $params);
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

    public function withDefaultData(): self
    {
        if ($this->isRoot()) {
            $this->data = $this->seoSet()->defaultData()->merge($this->data());
        }

        return $this;
    }

    public function fileData(): array
    {
        $data = $this->data()->all();

        if (Site::hasMultiple() && $this->hasOrigin()) {
            $data['origin'] = $this->origin()->locale();
        }

        if ($this->isRoot()) {
            $data = Arr::removeNullValues($data);
        }

        return $data;
    }

    protected function shouldRemoveNullsFromFileData()
    {
        return false;
    }

    public function site()
    {
        return Site::get($this->locale());
    }

    public function reference(): string
    {
        return "seo::{$this->id()}";
    }

    public function blueprint()
    {
        return $this->seoSet()->blueprint();
    }

    protected function getOriginByString($origin)
    {
        return $this->seoSet()->in($origin);
    }

    public function determineOrigin(Collection $sites): self
    {
        $defaultSite = Site::default()->handle();

        $origin = $sites->contains($defaultSite)
            ? $defaultSite
            : $sites->first();

        $this->locale === $origin
            ? $this->origin(null)
            : $this->origin($origin);

        return $this;
    }

    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedVariables($this);
    }
}
