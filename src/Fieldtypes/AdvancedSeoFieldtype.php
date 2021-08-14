<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Fields;
use Statamic\Fields\Fieldtype;
use Aerni\AdvancedSeo\Fields\OnPageSeoFields;
use Statamic\Facades\GlobalSet;
use Statamic\Support\Arr;

class AdvancedSeoFieldtype extends Fieldtype
{
    protected $selectable = false;
    protected $icon = 'seo-search-graph';

    public function preProcess($data): array
    {
        return $this->fields()->addValues($data ?? [])->preProcess()->values()->all();
    }

    public function preload(): array
    {
        return [
            'fields' => $this->fieldConfig(),
            'meta' => $this->fields()->addValues($this->field->value())->meta(),
        ];
    }

    public function process($data): array
    {
        return Arr::removeNullValues(
            $this->fields()->addValues($data)->process()->values()->all()
        );
    }

    protected function fields(): Fields
    {
        return new Fields($this->fieldConfig());
    }

    protected function fieldConfig(): array
    {
        if ($this->field()->parent() instanceof Entry || $this->field()->parent() instanceof Term) {
            $data = $this->field()->parent();
        }

        return OnPageSeoFields::data($data ?? null)->getConfig();
    }

    public function augment($data): mixed
    {
        if (! is_iterable($data)) {
            return $data;
        }

        return Blueprint::make()
            ->setContents(['fields' => $this->fieldConfig()])
            ->fields()
            ->addValues($data)
            ->augment()
            ->values()
            ->only(array_keys($data))
            ->all();
    }
}
