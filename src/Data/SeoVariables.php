<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Facades\Site;
use Statamic\Facades\Blink;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\GraphQL\ResolvesValues;
use Statamic\Contracts\Data\Augmented;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Localization;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Aerni\AdvancedSeo\Contracts\SeoVariablesRepository;
use Aerni\AdvancedSeo\Contracts\SeoVariables as Contract;

class SeoVariables implements Contract, Augmentable, Localization
{
    use ContainsData;
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use HasAugmentedInstance;
    use HasDefaultsData;
    use HasOrigin;
    use ResolvesValues {
        resolveGqlValue as traitResolveGqlValue;
    }

    protected SeoDefaultSet $set;

    protected string $locale;

    public function __construct()
    {
        $this->data = collect();
    }

    public function enabled(): bool
    {
        return $this->seoSet()->enabled();
    }

    public function seoSet($set = null)
    {
        return $this->fluentlyGetOrSet('set')
            ->setter(function ($set) {
                if ($set instanceof SeoDefaultSet) {
                    return $set;
                }

                [$type, $handle] = explode('::', $set);
                return Seo::find($type, $handle);
            })
            ->args(func_get_args());
    }

    public function locale($locale = null)
    {
        return $this->fluentlyGetOrSet('locale')->args(func_get_args());
    }

    public function id(): string
    {
        return "{$this->seoSet()->id()}::{$this->locale()}";
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
        return vsprintf('%s/%s/%s.yaml', [
            Stache::store('seo')->store($this->type())->directory(),
            $this->locale(),
            $this->handle(),
        ]);
    }

    public function editUrl(): string
    {
        return match ($this->type()) {
            'site' => cp_route('advanced-seo.site.defaults', [$this->handle(), $this->locale()]),
            'collections' => cp_route('advanced-seo.collections.defaults', [$this->handle(), $this->locale()]),
            'taxonomies' => cp_route('advanced-seo.taxonomies.defaults', [$this->handle(), $this->locale()]),
        };
    }

    public function save(): self
    {
        app(SeoVariablesRepository::class)->save($this);

        return $this;
    }

    public function delete(): bool
    {
        app(SeoVariablesRepository::class)->delete($this);

        return true;
    }

    public function defaultData(): Collection
    {
        // Get the default value of each field from the blueprint.
        $defaultData = $this->blueprint()->fields()->all()->map->defaultValue();

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

    public function blueprintFields(): array
    {
        // Get the field keys of the processed blueprint. This excludes any fields of disabled features.
        return $this->blueprint()->fields()->all()->keys()->all();
    }

    public function fileData(): array
    {
        return $this->data()
            ->only($this->blueprintFields())
            ->all();
    }

    protected function shouldRemoveNullsFromFileData()
    {
        return ! $this->hasOrigin();
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

    public function origin($origin = null)
    {
        if (func_num_args() === 0) {
            if ($found = Blink::get($this->getOriginBlinkKey())) {
                return $found;
            }

            $origin = $this->seoSet()->origins()->get($this->locale());

            return tap($this->getOriginByString($origin), function ($found) {
                Blink::put($this->getOriginBlinkKey(), $found);
            });
        }

        // Ensure we don't make a localization its own origin
        if ($origin === $this->locale()) {
            return $this;
        }

        // Verify that the origin is valid.
        if (! $this->seoSet()->sites()->has($origin)) {
            return $this;
        }

        Blink::forget($this->getOriginBlinkKey());

        $origins = $this->seoSet()->origins()->put($this->locale(), $origin)->all();

        $this->seoSet()->origins($origins);

        return $this;
    }

    protected function getOriginByString($origin)
    {
        return is_null($origin) ? null : $this->seoSet()->in($origin);
    }

    public function resolveGqlValue(string $field)
    {
        if (! in_array($field, $this->blueprintFields())) {
            return null;
        }

        return $this->traitResolveGqlValue($field);
    }

    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedVariables($this);
    }
}
