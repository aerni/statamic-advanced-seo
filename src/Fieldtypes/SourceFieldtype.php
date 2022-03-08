<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\Fieldtypes\Code;

class SourceFieldtype extends Fieldtype
{
    protected static $handle = 'seo_source';
    protected $selectable = false;

    public function preProcess(mixed $data): array
    {
        return match ($data) {
            '@default' => ['source' => 'default', 'value' => $this->sourceFieldDefaultValue()],
            '@auto' => ['source' => 'auto', 'value' => $this->sourceFieldtype()->preProcess(null)],
            '@null' => ['source' => 'custom', 'value' => $this->sourceFieldtype()->preProcess(null)],
            default => ['source' => 'custom', 'value' => $this->sourceFieldtype()->preProcess($data)],
        };
    }

    public function process(mixed $data): mixed
    {
        if ($data === null) {
            return $data;
        }

        if ($data['source'] === 'default') {
            return '@default';
        }

        if ($data['source'] === 'auto') {
            return '@auto';
        }

        // We only want to handle empty values that are not booleans. Booleans should be saved as such.
        if ($data['source'] === 'custom' && empty($data['value']) && ! is_bool($data['value'])) {
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
            'title' => $this->parentTitle(),
        ];
    }

    public function augment(mixed $data): mixed
    {
        if ($data === '@default' || $data === null) {
            $defaultValue = $this->sourceField()->setValue(null)->defaultValue();

            return $this->sourceFieldtype()->augment($defaultValue);
        }

        if ($data === '@auto') {
            $fieldHandle = $this->field->config()['auto'];
            $parent = $this->field->parent();
            $field = $parent->blueprint()->fields()->get($fieldHandle);
            $value = $parent->value($fieldHandle);

            return $field->setValue($value)->fieldtype()->augment($field->value());
        }

        if ($data === '@null') {
            return $this->sourceFieldtype()->augment(null);
        }

        return $this->sourceFieldtype()->augment($data);
    }

    public function preProcessValidatable(mixed $value): mixed
    {
        if ($value === null) {
            return $value;
        }

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

    protected function parentTitle(): ?string
    {
        $parent = $this->field->parent();

        return match (true) {
            ($parent instanceof Entry) => $parent->collection()->title(),
            ($parent instanceof Term) => $parent->taxonomy()->title(),
            ($parent instanceof Taxonomy) => $parent->title(),
            ($parent instanceof Collection) => $parent->title(),
            default => null,
        };
    }
}
