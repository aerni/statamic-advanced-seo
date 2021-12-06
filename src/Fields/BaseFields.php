<?php

namespace Aerni\AdvancedSeo\Fields;

use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Contracts\Fields;
use Aerni\AdvancedSeo\Traits\GetsSiteDefaults;
use Aerni\AdvancedSeo\Traits\GetsContentDefaults;

abstract class BaseFields implements Fields
{
    use GetsContentDefaults;
    use GetsSiteDefaults;

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

    // TODO: Refactor this to use a new cascade class.
    protected function getValueFromCascade($handle): ?string
    {
        // We can't get any defaults with no data.
        if (! $this->data) {
            return null;
        }

        // We only need this data for the blueprints in the CP.
        if (! str_contains(request()->path(), config('cp.route', 'cp'))) {
            return null;
        }

        $siteDefaults = $this->getSiteDefaults($this->data);
        $contentDefaults = $this->getContentDefaults($this->data);

        $cascade = $siteDefaults->merge($contentDefaults);

        return $cascade->get($handle)?->raw();
    }

    abstract protected function sections(): array;
}
