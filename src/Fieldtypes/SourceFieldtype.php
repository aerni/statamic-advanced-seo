<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\Fieldtypes\Code;

class SourceFieldtype extends Fieldtype
{
    protected static $handle = 'seo_source';
    protected $selectable = false;

    public function preProcess(mixed $data): array
    {
        if ($data === '@default') {
            return ['source' => 'default', 'value' => $this->sourceFieldDefaultValue()];
        }

        if ($data === '@null') {
            return ['source' => 'custom', 'value' => ''];
        }

        return ['source' => 'custom', 'value' => $this->sourceFieldtype()->preProcess($data)];
    }

    public function process(mixed $data): mixed
    {
        if ($data['source'] === 'default') {
            return '@default';
        }

        if ($data['value'] === null) {
            return '@null';
        }

        // Handle the Assets fieldtype.
        if ($data['value'] === []) {
            return '@null';
        }

        // Handle the Code fieldtype.
        if ($this->sourceFieldtype() instanceof Code && $data['value']['code'] === '') {
            return '@null';
        }

        return $this->sourceFieldtype()->process($data['value']);
    }

    public function preload(): array
    {
        return [
            'default' => $this->sourceFieldDefaultValue(),
            'defaultMeta' => $this->sourceFieldDefaultMeta(),
            'meta' => $this->sourceFieldMeta(),
        ];
    }

    public function augment(mixed $data): mixed
    {
        /**
         * TODO: If the value is null it won't correctly get the value from the cascade.
         * How can we fix this?
         */
        if ($data === '@default' || $data === null) {
            $defaultValue = $this->sourceField()->setValue(null)->defaultValue();

            return $this->sourceFieldtype()->augment($defaultValue);
        }

        if ($data === '@null') {
            return $this->sourceFieldtype()->augment(null);
        }

        return $this->sourceFieldtype()->augment($data);
    }

    public function preProcessValidatable(mixed $value): mixed
    {
        return $value['value'];
    }

    public function rules(): array
    {
        return $this->sourceFieldtype()->rules();
    }

    public function fieldRules(): ?array
    {
        return $this->sourceFieldtype()->fieldRules();
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

    protected function sourceFieldMeta(): mixed
    {
        return $this->sourceField()->setValue($this->sourceFieldValue())->preProcess()->meta();
    }

    protected function sourceFieldValue(): mixed
    {
        return $this->field->value()['value'];
    }
}
