<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Models\Conditions;
use Aerni\AdvancedSeo\View\SourceFieldtypeCascade;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
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
        /**
         * Augment null when encountering a field of a disabled feature.
         * This is necessary for fields like `seo_generate_social_images` that can be hidden (disabled)
         * on a collection and site level. This ensures that no data is returned when this field is augmented
         * on the frontend and when using GraphQL. It's like "soft" removing the field from the blueprint.
         * We can't actually remove the field from the blueprint because that wouldn't work with GraphQL.
         */
        if ($this->isDisabledFeature()) {
            return $this->sourceFieldtype()->augment(null);
        }

        $data = $data ?? $this->field->defaultValue();

        if ($data === '@default') {
            return $this->sourceFieldtype()->augment($this->defaultValueFromCascade());
        }

        if ($data === '@auto') {
            $field = $this->field->config()['auto'];
            $parent = $this->field->parent();

            return $parent->$field;
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

        $cascade = Blink::once("advanced-seo::cascade::fieldtype::{$data->id()}", fn () => SourceFieldtypeCascade::from($data)->process());

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

    protected function isDisabledFeature(): bool
    {
        $fieldConditions = collect($this->field->conditions())
            ->flatMap(fn ($condition) => Str::remove('custom ', $condition))
            ->flip();

        $conditions = Conditions::all()->intersectByKeys($fieldConditions);

        // The feature is enabled if there are no conditions.
        if ($conditions->isEmpty()) {
            return false;
        }

        $data = GetDefaultsData::handle($this->field->parent());

        $evaluatedConditions = $conditions->map(fn ($condition) => app($condition)::handle($data));

        // The feature is disabled if the conditions evaluate to false.
        return $evaluatedConditions->filter()->isEmpty();
    }
}
