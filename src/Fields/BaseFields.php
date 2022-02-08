<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\GetsContentDefaults;
use Aerni\AdvancedSeo\Concerns\GetsSiteDefaults;
use Aerni\AdvancedSeo\Concerns\ShouldHandleRoute;
use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\View\Cascade;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

abstract class BaseFields implements Fields
{
    use GetsContentDefaults;
    use GetsSiteDefaults;
    use ShouldHandleRoute;

    protected Entry|Term|Collection $data;

    public function __construct()
    {
        $this->data = collect();
    }

    public static function make(): self
    {
        return new static();
    }

    public function data(Entry|Term|Collection $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function get(): array
    {
        return array_flatten($this->sections(), 1);
    }

    public function items(): array
    {
        return collect($this->get())->mapWithKeys(function ($field) {
            return [$field['handle'] => $field['field']];
        })->toArray();
    }

    protected function getValueFromCascade(string $handle, mixed $default = null): mixed
    {
        // We only need this data for the blueprints in the CP.
        if (! $this->isCpRoute()) {
            return null;
        }

        // We can't get any defaults with no data.
        if ($this->data instanceof Collection && $this->data->isEmpty()) {
            return null;
        }

        $value = Cascade::from($this->data)
            ->withSiteDefaults()
            ->withContentDefaults()
            ->value(Str::remove('seo_', $handle));

        return $value ?? $default;
    }

    protected function trans(string $parent, string $key): string
    {
        return __("advanced-seo::fields.$parent.$key", ['type' => $this->typePlaceholder()]);
    }

    protected function typePlaceholder(): string
    {
        if ($this->data instanceof Entry) {
            return 'entry';
        }

        if ($this->data instanceof Term) {
            return 'term';
        }

        if ($this->data instanceof Collection && $this->data->get('type') === 'collections') {
            return 'entries';
        }

        if ($this->data instanceof Collection && $this->data->get('type') === 'taxonomies') {
            return 'terms';
        }

        return '';
    }

    abstract protected function sections(): array;
}
