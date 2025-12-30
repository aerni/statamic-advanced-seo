<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Aerni\AdvancedSeo\Contracts\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as Contract;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Illuminate\Support\Collection;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Augmented;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Blink;
use Statamic\Facades\Path;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\GraphQL\ResolvesValues;
use Statamic\Sites\Site as SiteObject;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoSetLocalization implements Augmentable, Contract
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

    public function __construct(
        protected readonly string $seoSet,
        protected readonly string $locale,
    ) {
        $this->data = collect();
    }

    public function seoSet(): SeoSet
    {
        return Blink::once("advanced-seo::{$this->seoSet}", fn () => Seo::find($this->seoSet));
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function id(): string
    {
        return "{$this->seoSet()->id()}::{$this->locale()}";
    }

    public function handle(): string
    {
        return $this->seoSet()->handle;
    }

    public function title(): string
    {
        return $this->seoSet()->title;
    }

    public function type(): string
    {
        return $this->seoSet()->type;
    }

    public function path(): string
    {
        return Path::assemble(
            Stache::store('seo-set-configs')->directory(),
            $this->type(),
            $this->locale(),
            "{$this->handle()}.yaml"
        );
    }

    public function editUrl(): string
    {
        return cp_route('advanced-seo.sets.localization', [
            'seoSetGroup' => $this->type(),
            'seoSet' => $this->handle(),
            'seoSetLocalization' => $this->locale(),
        ]);
    }

    public function save(): self
    {
        $config = $this->seoSet()->config();

        if (! $config->initialPath()) {
            $config->save();
        }

        SeoLocalization::save($this);

        return $this;
    }

    public function delete(): bool
    {
        SeoLocalization::delete($this);

        return true;
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
        return $this->isRoot();
    }

    public function sites(): Collection
    {
        return $this->seoSet()->sites();
    }

    public function site(): SiteObject
    {
        return Site::get($this->locale());
    }

    public function blueprint(): Blueprint
    {
        return $this->seoSet()
            ->defaultsData($this->defaultsData())
            ->blueprint();
    }

    public function origin(?string $origin = null): Contract|self|null
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

        $this->seoSet()->config()->origins($origins);

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
        return new AugmentedLocalization($this);
    }
}
