<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Contracts\Fields;
use Statamic\Contracts\Entries\Entry;

abstract class BaseFields implements Fields
{
    protected $data;

    public static function make(): self
    {
        return new static();
    }

    public function data($data): self
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

    protected function type(): string
    {
        if (is_array($this->data)) {
            return $this->data['type'] === 'collections'
                ? 'entries'
                : 'terms';
        }

        return $this->data instanceof Entry
            ? 'entry'
            : 'term';
    }

    protected function trans(string $parent, string $key): string
    {
        return __("advanced-seo::fields.$parent.$key", ['type' => $this->type()]);
    }

    abstract protected function sections(): array;
}
