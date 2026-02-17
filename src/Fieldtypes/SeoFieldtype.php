<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Support\Helpers;
use Aerni\AdvancedSeo\View\SeoFieldtypeCascade;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\Fieldtypes\Code;

class SeoFieldtype extends Fieldtype
{
    protected static $handle = 'seo';

    protected $defaultValue = '@default';

    protected $selectable = false;

    public function preload(): array
    {
        return [
            'component' => $this->childFieldtype()->component().'-fieldtype',
            'meta' => $this->childMeta(),
            'defaultValue' => $this->childDefaultValue(),
            'defaultMeta' => $this->childDefaultMeta(),
            'originDefaultValue' => $this->originDefaultValue(),
            'isTextBasedField' => $this->isTextBasedField(),
        ];
    }

    public function preProcess(mixed $data): array
    {
        $data ??= $this->field->defaultValue();

        if ($data === '@default') {
            return [
                'source' => 'default',
                'value' => $this->shouldUseOriginDefault() ? $this->originDefaultValue() : $this->childDefaultValue(),
            ];
        }

        return [
            'source' => 'custom',
            'value' => $this->childFieldtype()->preProcess($data),
        ];
    }

    /**
     * In multi-site, a localized entry synced to its origin inherits the origin's
     * cascade defaults. For non-text fields (toggles, selects) that can't use
     * placeholders, we need to pass the origin's resolved value when it differs
     * from the local cascade — otherwise the field would show the wrong state.
     *
     * The Vue component detects this via the showsOriginDefault computed.
     */
    protected function shouldUseOriginDefault(): bool
    {
        // Text fields use placeholders — they always show the local default.
        if ($this->isTextBasedField()) {
            return false;
        }

        // Only relevant when the field inherits from the origin entry.
        if (! $this->isSyncedToOrigin()) {
            return false;
        }

        // Use the origin's default when it differs from the local cascade,
        // e.g. origin has noindex=true but local cascade resolves to false.
        return $this->childDefaultValue() !== $this->originDefaultValue();
    }

    protected function isSyncedToOrigin(): bool
    {
        $parent = $this->localizedParent();

        return $parent?->hasOrigin() && ! $parent->data()->has($this->field->handle());
    }

    protected function localizedParent(): mixed
    {
        $parent = $this->field->parent();

        if (! ($parent instanceof Entry || $parent instanceof Term)) {
            return null;
        }

        return Helpers::localizedContent($parent);
    }

    public function process(mixed $data): mixed
    {
        // Statamic passes null when the field isn't in the form submission.
        if ($data === null) {
            return $data;
        }

        if ($data['source'] === 'default') {
            return '@default';
        }

        // Safety net for non-text fields (assets, selects) that may submit empty custom values.
        // Text fields are already handled by the Vue's handleBlur before save.
        if (empty($data['value']) && ! is_bool($data['value'])) {
            return '@default';
        }

        // Code fieldtype: empty code reverts to default.
        if ($this->childFieldtype() instanceof Code && $data['value']['code'] === '') {
            return '@default';
        }

        return $this->childFieldtype()->process($data['value']);
    }

    public function augment(mixed $data): mixed
    {
        $data ??= $this->field->defaultValue();

        if ($data === '@default') {
            $data = $this->defaultValueFromCascade();
        }

        return $this->childFieldtype()->augment($this->parseAntlers($data));
    }

    public function preProcessValidatable(mixed $value): mixed
    {
        return Arr::get($value, 'value');
    }

    public function rules(): array
    {
        return $this->childFieldtype()->rules();
    }

    public function fieldRules(): ?array
    {
        return $this->childFieldtype()->fieldRules();
    }

    public function toQueryableValue(mixed $value): mixed
    {
        return $this->augment($value);
    }

    public function toGqlType(): mixed
    {
        $type = $this->childFieldtype()->toGqlType();

        // Child fieldtype resolvers (e.g. select, dictionary) call $item->resolveGqlValue()
        // expecting an Entry or similar parent. The SeoFieldtype wrapper is consumed by types
        // like RawMetaDataType where the parent is an AugmentedCollection which doesn't have
        // that method. Each consuming type provides its own resolver, so we strip the child's.
        if (is_array($type)) {
            unset($type['resolve']);
        }

        return $type;
    }

    public function addGqlTypes(): void
    {
        $this->childFieldtype()->addGqlTypes();
    }

    protected function childField(): Field
    {
        return new Field(null, $this->config('field'))
            ->setParent($this->field->parent());
    }

    protected function childFieldtype(): Fieldtype
    {
        return $this->childField()->fieldtype();
    }

    protected function childMeta(): mixed
    {
        return $this->childField()
            ->setValue($this->field->value()['value'])
            ->preProcess()
            ->meta();
    }

    protected function childDefaultValue(mixed $parent = null): mixed
    {
        return $this->childField()->setValue($this->defaultValueFromCascade($parent))->preProcess()->value();
    }

    protected function childDefaultMeta(): mixed
    {
        return $this->childField()->setValue($this->defaultValueFromCascade())->preProcess()->meta();
    }

    /**
     * The origin's cascade default for non-text fields synced to origin.
     * Needed because toggles/selects have no placeholder — the JS uses this
     * to detect sync swaps and show the reset button.
     */
    protected function originDefaultValue(): mixed
    {
        $parent = $this->localizedParent();

        if (! $parent?->hasOrigin()) {
            return null;
        }

        return $this->childDefaultValue($parent->origin());
    }

    protected function isTextBasedField(): bool
    {
        return in_array('text', $this->childFieldtype()->categories());
    }

    protected function parseAntlers(mixed $data): mixed
    {
        if (! is_string($data)) {
            return $data;
        }

        if (! Str::contains($data, '{{')) {
            return $data;
        }

        $parent = $this->field->parent();

        if ($parent instanceof Term) {
            $parent = $parent->in(Context::from($parent)->site);
        }

        $parentData = $parent->toAugmentedArray();

        // Prevent self-reference: remove the current field from context
        // so {{ seo_title }} inside seo_title doesn't cause infinite recursion.
        unset($parentData[$this->field->handle()]);

        return (string) Antlers::parse($data, $parentData);
    }

    protected function defaultValueFromCascade(mixed $parent = null): mixed
    {
        $context = Context::from($parent ?? $this->field->parent());

        $cascade = Blink::once("advanced-seo::cascade::fieldtype::{$context->id()}", fn () => SeoFieldtypeCascade::from($context));

        $key = Str::remove('seo_', $this->field->handle());

        $value = $cascade->value($key);

        return match (true) {
            ($value instanceof Entry) => $value->id(),
            ($value instanceof Asset) => $value->path(),
            ($value instanceof Arrayable) => $value->value(),
            default => $value,
        };
    }
}
