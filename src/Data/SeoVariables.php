<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
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
use Statamic\Fields\Blueprint;
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
    use HasDefaultsData;

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
            Site::hasMultiple() ? $this->locale().'/' : '',
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

    public function defaultData(): Collection
    {
        // Get the default value of each field from the blueprint.
        $defaultData = $this->blueprint()->fields()->all()->map->defaultValue();

        // TODO: We used to evaluate the conditions so we don't save default values of disabled features to file.
        // Should we really do this?

        // Only keep default fields with values that should be saved to file.
        return $defaultData->filter(fn ($value) => $value !== null && $value !== []);
    }

    public function withDefaultData(): self
    {
        if ($this->isRoot()) {
            $this->data = $this->defaultData()->merge($this->data());
        }

        if ($this->hasOrigin()) {
            $this->data = $this->defaultData()
                ->diffAssoc($this->origin()->defaultData())
                ->merge($this->data());
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

        /**
         * TODO: Should we remove any values that don't have a field on the blueprint?
         * This should also take the conditions into consideration. This ensures that fields of deactivated features will be removed.
         */

        return $data;
    }

    protected function shouldRemoveNullsFromFileData()
    {
        return false;
    }

    public function sites(): Collection
    {
        return $this->seoSet()->sites();
    }

    public function site()
    {
        return Site::get($this->locale());
    }

    public function reference(): string
    {
        return "seo::{$this->id()}";
    }

    public function blueprint(): Blueprint
    {
        return $this->seoSet()
            ->defaultsData($this->defaultsData())
            ->blueprint();
    }

    protected function getOriginByString($origin)
    {
        return $this->seoSet()->in($origin);
    }

    // TODO: Might be able to not accept $sites but use $this->seoSet->sites() instead.
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
