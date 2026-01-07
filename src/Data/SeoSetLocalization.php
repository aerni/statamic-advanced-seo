<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Aerni\AdvancedSeo\Concerns\HasDefaultValues;
use Aerni\AdvancedSeo\Concerns\HasSeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as Contract;
use Aerni\AdvancedSeo\Enums\Context;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationDeleted;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationSaved;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Illuminate\Support\Collection;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Augmented;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Data\HasOrigin;
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
    use HasDefaultValues;
    use HasOrigin;
    use HasSeoSet;
    use ResolvesValues {
        resolveGqlValue as traitResolveGqlValue;
    }

    protected string $locale;

    public function __construct()
    {
        $this->data = collect();
    }

    public function locale(?string $locale = null): string|self
    {
        return $this->fluentlyGetOrSet('locale')->args(func_get_args());
    }

    public function id(): string
    {
        return "{$this->seoSet()->id()}::{$this->locale()}";
    }

    public function type(): string
    {
        return $this->seoSet()->type();
    }

    public function handle(): string
    {
        return $this->seoSet()->handle();
    }

    public function title(): string
    {
        return $this->seoSet()->title();
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
        SeoLocalization::save($this);

        SeoSetLocalizationSaved::dispatch($this);

        return $this;
    }

    public function delete(): bool
    {
        SeoLocalization::delete($this);

        SeoSetLocalizationDeleted::dispatch($this);

        return true;
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
        return resolve($this->seoSet()->blueprint('localization'))
            ->make()
            ->data($this->defaultsData())
            ->get();
    }

    public function blueprintFields(): array
    {
        // Get the field keys of the processed blueprint. This excludes any fields of disabled features.
        return $this->blueprint()->fields()->all()->keys()->all();
    }

    public function origin(): ?Contract
    {
        return ($origin = $this->seoSet()->origins()->get($this->locale()))
            ? $this->getOriginByString($origin)
            : null;
    }

    protected function getOriginByString($origin): ?Contract
    {
        return $this->seoSet()->in($origin);
    }

    protected function context(): Context
    {
        return Context::LOCALIZATION;
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
