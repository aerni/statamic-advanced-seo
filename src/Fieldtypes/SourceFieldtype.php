<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\EvaluateModelLocale;
use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\View\SourceFieldtypeCascade;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
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
            '@auto' => ['source' => 'auto', 'value' => $this->autoValue()],
            '@null' => ['source' => 'custom', 'value' => $this->sourceFieldtype()->preProcess(null)],
            default => ['source' => 'custom', 'value' => $this->sourceFieldtype()->preProcess($data)],
        };
    }

    public function process(mixed $data): mixed
    {
        if ($data === null) {
            return $data;
        }

        // Dont't save the value if it's the same as the field's default. We don't want to unnecessarily spam the entry data.
        if (Str::contains($this->config('default'), $data['source'])) {
            return null;
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
        $data = $data ?? $this->field->defaultValue();

        if ($data === '@default') {
            $value = $this->sourceFieldtype()->augment($this->defaultValueFromCascade());

            return is_string($value) && Str::contains($value, '@field')
                ? $this->sourceFieldtype()->augment($this->parseFieldValues($value))
                : $value;
        }

        if ($data === '@auto') {
            return $this->autoValue();
        }

        if (is_string($data) && Str::contains($data, '@field')) {
            return $this->sourceFieldtype()->augment($this->parseFieldValues($data));
        }

        if ($data === '@null') {
            return $this->sourceFieldtype()->augment(null);
        }

        return $this->sourceFieldtype()->augment($data);
    }

    public function preProcessValidatable(mixed $value): mixed
    {
        return Arr::get($value, 'value');
    }

    public function rules(): array
    {
        return $this->sourceFieldtype()->rules();
    }

    public function fieldRules(): ?array
    {
        return $this->sourceFieldtype()->fieldRules();
    }

    public function toQueryableValue(mixed $value): mixed
    {
        return $this->augment($value);
    }

    public function toGqlType(): mixed
    {
        return $this->sourceFieldtype()->toGqlType();
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
        return $this->sourceField()->setValue($this->defaultValueFromCascade())->preProcess()->value();
    }

    protected function sourceFieldDefaultMeta(): mixed
    {
        return $this->sourceField()->setValue($this->defaultValueFromCascade())->preProcess()->meta();
    }

    protected function autoValue(): mixed
    {
        $parent = $this->field->parent();
        $field = $this->config('auto');

        return match (true) {
            ($parent instanceof Entry) => $parent->$field,
            ($parent instanceof Term) => $parent->in(EvaluateModelLocale::handle($parent))->$field,
            default => null
        };
    }

    protected function parseFieldValues($data): mixed
    {
        $parent = $this->field->parent();

        if ($parent instanceof Term) {
            $parent = $parent->in(EvaluateModelLocale::handle($parent));
        }

        $parent = $parent->toAugmentedArray();

        // Prevent infinite loop by removing the field if it's part of the data.
        $data = Str::of($data)->remove("@field:{$this->field->handle()}")->trim();

        preg_match_all('/@field:([A-z\d+-_]+)/', $data, $matches);

        $fieldValues = [];

        foreach ($matches[1] as $field) {
            $fieldValues["@field:{$field}"] = Antlers::parser()->getVariable($field, $parent);
        }

        return strtr($data, $fieldValues);
    }

    protected function sourceFieldMeta(): mixed
    {
        return $this->sourceField()->setValue($this->sourceFieldValue())->preProcess()->meta();
    }

    protected function sourceFieldValue(): mixed
    {
        return $this->field->value()['value'];
    }

    protected function defaultValueFromCascade(): mixed
    {
        $data = GetDefaultsData::handle($this->field->parent());

        // We can't get any data on default views like '/cp/advanced-seo/collections/pages'.
        if (! $data) {
            return null;
        }

        $cascade = Blink::once("advanced-seo::cascade::fieldtype::{$data->id()}", fn () => SourceFieldtypeCascade::from($data));

        $value = $cascade->value(Str::remove('seo_', $this->field->handle()));

        $value = match (true) {
            ($value instanceof Entry) => $value->id(),
            ($value instanceof Asset) => $value->path(),
            ($value instanceof Arrayable) => $value->value(),
            default => $value,
        };

        $fallbackValue = $this->sourceField()->setValue(null)->defaultValue();

        return $value ?? $fallbackValue;
    }

    protected function parentTitle(): ?string
    {
        $parent = $this->field->parent();

        return match (true) {
            ($parent instanceof Collection) => $parent->title(),
            ($parent instanceof Entry) => $parent->collection()->title(),
            ($parent instanceof Taxonomy) => $parent->title(),
            ($parent instanceof Term) => $parent->taxonomy()->title(),
            default => null,
        };
    }
}
