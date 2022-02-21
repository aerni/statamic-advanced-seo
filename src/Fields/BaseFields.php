<?php

namespace Aerni\AdvancedSeo\Fields;

use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Illuminate\Support\Str;
use Statamic\Facades\Blink;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\View\Cascade;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Contracts\Fields;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\Concerns\GetsSiteDefaults;
use Aerni\AdvancedSeo\Concerns\ShouldHandleRoute;
use Aerni\AdvancedSeo\Concerns\GetsContentDefaults;

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

    public static function getDefaultValue(string $key): ?Value
    {
        return self::getDefaultValues()->get($key);
    }

    public static function getDefaultValues(): Collection
    {
        return collect((new static)->sections())->flatten(1)->mapWithKeys(function ($item) {
            $field = new Field($item['handle'], $item['field']);
            $augmented = $field->setValue($field->defaultValue())->augment()->value();

            return [$item['handle'] => $augmented];
        });
    }

    protected function getValueFromCascade(string $handle): mixed
    {
        // We can't get any defaults with no data.
        if ($this->data instanceof Collection && $this->data->isEmpty()) {
            return null;
        }

        return Blink::once('advanced-seo::cascade::cp', function () {
            return Cascade::from($this->data)->withContentDefaults();
        })->value(Str::remove('seo_', $handle));
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
