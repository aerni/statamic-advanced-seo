<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Support\Helpers;

abstract class BaseFields implements Fields
{
    protected DefaultsData $data;

    public static function make(): self
    {
        return new static();
    }

    // TODO: We can probaly safely remove this, now that we are using JS conditions?
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

    protected function trans(string $key, array $placeholders = []): ?string
    {
        if (! isset($this->data)) {
            return null;
        }

        $placeholders = array_merge(['type' => $this->typePlaceholder()], $placeholders);

        return __("advanced-seo::fields.$key", $placeholders);
    }

    protected function typePlaceholder(): ?string
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
            default => null
        };
    }

    abstract protected function sections(): array;
}
