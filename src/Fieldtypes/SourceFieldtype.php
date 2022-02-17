<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;

class SourceFieldtype extends Fieldtype
{
    protected static $handle = 'seo_source';
    protected $selectable = false;

    public function preProcess(mixed $data): mixed
    {
        return $data === '@default' ? null : $data;
    }

    public function process(mixed $data): mixed
    {
        return is_null($data) ? '@default' : $this->sourceFieldtype()->process($data);
    }

    public function preload(): array
    {
        return [
            'source' => $this->field->value() ? 'custom' : 'default',
            'default' => $this->sourceField()->defaultValue(),
        ];
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
