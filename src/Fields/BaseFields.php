<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\GetsContentDefaults;
use Aerni\AdvancedSeo\Concerns\GetsSiteDefaults;
use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\View\Cascade;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Blink;

abstract class BaseFields implements Fields
{
    use GetsContentDefaults;
    use GetsSiteDefaults;

    protected DefaultsData $data;

    public static function make(): self
    {
        return new static();
    }

    public function data(DefaultsData $data): self
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

    public function getValueFromCascade(string $handle): mixed
    {
        // We can't create a cascade if we don't have any data.
        if (! isset($this->data)) {
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

    protected function typePlaceholder(): ?string
    {
        return match ($this->data->type) {
            'collections' => 'entry',
            'taxonomies' => 'term',
            default => null
        };
    }

    abstract protected function sections(): array;
}
