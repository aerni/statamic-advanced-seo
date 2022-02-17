<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;

class SourceFieldtype extends Fieldtype
{
    protected static $handle = 'seo_source';
    protected $selectable = false;

    public function preProcess(mixed $data): array
    {
        return $data === '@default'
            ? ['source' => 'default', 'value' => null]
            : ['source' => 'custom', 'value' => $data];
    }

    public function process(mixed $data): mixed
    {
        return $data['source'] === 'default'
            ? '@default'
            : $this->sourceFieldtype()->process($data['value']);
    }

    public function augment(mixed $data): mixed
    {
        if ($data === '@default') {
            $data = $this->sourceField()->defaultValue();
        }

        return $this->sourceFieldtype()->augment($data);
    }

    protected function sourceField(): Field
    {
        return new Field(null, $this->config('field'));
    }

    protected function sourceFieldtype(): Fieldtype
    {
        return $this->sourceField()->fieldtype();
    }
}
