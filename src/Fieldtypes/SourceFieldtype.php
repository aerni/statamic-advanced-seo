<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Field;
use Illuminate\Support\Str;
use Statamic\Facades\Blink;
use Statamic\Fieldtypes\Code;
use Statamic\Fields\Fieldtype;
use Aerni\AdvancedSeo\View\Cascade;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Entries\Collection;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Actions\GetDefaultsData;

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
        $data = Blink::once('advanced-seo::source-fieldtype::parent', fn () => GetDefaultsData::handle($this->field->parent()));

        if (! $data) {
            return null;
        }

        $cascade = Blink::once("advanced-seo::cascade::cp", fn () => Cascade::from($data)->processForBlueprint());

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
            ($parent instanceof Entry) => $parent->collection()->title(),
            ($parent instanceof Term) => $parent->taxonomy()->title(),
            ($parent instanceof Taxonomy) => $parent->title(),
            ($parent instanceof Collection) => $parent->title(),
            default => null,
        };
    }
}
