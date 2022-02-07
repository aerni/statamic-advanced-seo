<?php

namespace Aerni\AdvancedSeo\Fields;

use Illuminate\Support\Str;
use Aerni\AdvancedSeo\View\Cascade;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\Concerns\GetsSiteDefaults;
use Aerni\AdvancedSeo\Concerns\ShouldHandleRoute;
use Aerni\AdvancedSeo\Concerns\GetsContentDefaults;

abstract class BaseFields implements Fields
{
    use GetsContentDefaults;
    use GetsSiteDefaults;
    use ShouldHandleRoute;

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

    protected function getValueFromCascade(string $handle, $default = null): ?string
    {
        // We can't get any defaults with no data.
        if (! $this->data) {
            return null;
        }

        // We only need this data for the blueprints in the CP.
        if (! $this->isCpRoute()) {
            return null;
        }

        $value = Cascade::from($this->data)
            ->withSiteDefaults()
            ->withContentDefaults()
            ->process()
            ->value(Str::remove('seo_', $handle));

        return $value ?? $default;
    }

    abstract protected function sections(): array;
}
