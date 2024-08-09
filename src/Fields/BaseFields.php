<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Arr;

abstract class BaseFields implements Fields
{
    protected DefaultsData $data;

    public static function make(): self
    {
        return new static;
    }

    public function data(DefaultsData $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function get(): array
    {
        return [
            [
                'fields' => Arr::flatten($this->sections(), 1),
            ],
        ];
    }

    public function items(): array
    {
        return collect($this->get())
            ->flatten(2)
            ->mapWithKeys(fn ($field) => [$field['handle'] => $field['field']])
            ->toArray();
    }

    protected function trans(string $key, array $placeholders = []): ?string
    {
        if (! isset($this->data)) {
            return null;
        }

        $placeholders = array_merge(['type' => $this->typePlaceholder()], $placeholders);

        return __("advanced-seo::fields.$key", $placeholders);
    }

    protected function typePlaceholder(): string
    {
        if (! isset($this->data)) {
            return '';
        }

        return match ($this->data->type) {
            'collections' => Helpers::isAddonCpRoute()
                ? lcfirst(__('advanced-seo::messages.entries'))
                : lcfirst(__('advanced-seo::messages.entry')),
            'taxonomies' => Helpers::isAddonCpRoute()
                ? lcfirst(__('advanced-seo::messages.terms'))
                : lcfirst(__('advanced-seo::messages.term')),
            default => ''
        };
    }

    abstract protected function sections(): array;
}
