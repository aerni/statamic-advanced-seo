<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;

class SourceFieldtype extends Fieldtype
{
    protected static $handle = 'seo_source';
    protected $selectable = false;

    public function rules(): array
    {
        return $this->sourceFieldtype()->rules();
    }

    public function fieldRules(): ?array
    {
        return $this->sourceFieldtype()->fieldRules();
    }

    public function preProcess(mixed $data): mixed
    {
        if (is_null($data) || $data === '@default') {
            return $this->sourceFieldDefaultValue();
        }

        return $this->sourceFieldtype()->preProcess($data);
    }

    public function process(mixed $data): mixed
    {
        return $this->isDefaultValue($data)
            ? '@default' // TODO: Should we just save null?
            : $this->sourceFieldtype()->process($data);
    }

    public function preload(): array
    {
        return [
            'source' => $this->source(),
            'default' => $this->sourceFieldDefaultValue(),
            'defaultMeta' => $this->sourceFieldDefaultMeta(),
            'meta' => $this->sourceFieldMeta(),
        ];
    }

    public function augment(mixed $data): mixed
    {
        if ($data === '@default') {
            $data = $this->sourceFieldDefaultValue();
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

    protected function sourceFieldDefaultValue(): mixed
    {
        return $this->sourceField()->setValue(null)->preProcess()->value();
    }

    protected function sourceFieldDefaultMeta(): mixed
    {
        return $this->sourceField()->setValue(null)->preProcess()->meta();
    }

    protected function sourceFieldMeta()
    {
        return $this->sourceField()->setValue($this->sourceFieldValue())->preProcess()->meta();
    }

    protected function isDefaultValue(mixed $value): mixed
    {
        return $value === $this->sourceFieldDefaultValue();
    }

    protected function source(): string
    {
        return $this->isDefaultValue($this->sourceFieldValue()) ? 'default' : 'custom';
    }

    protected function sourceFieldValue(): mixed
    {
        return $this->field->value();
    }
}
