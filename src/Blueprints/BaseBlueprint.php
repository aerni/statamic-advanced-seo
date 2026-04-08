<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Closure;
use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;
use Statamic\Support\Str;

abstract class BaseBlueprint
{
    protected ?Context $context = null;

    abstract protected function handle(): string;

    abstract protected function tabs(): array;

    public static function make(): static
    {
        return new static;
    }

    public static function resolve(mixed $model): Blueprint
    {
        return static::make()->for($model)->get();
    }

    public static function definition(): Blueprint
    {
        return static::make()->get();
    }

    public function for(mixed $model): static
    {
        $this->context = Context::from($model);

        return $this;
    }

    public function get(): Blueprint
    {
        return \Statamic\Facades\Blueprint::make()
            ->setHandle($this->handle())
            ->setContents($this->contents());
    }

    protected function contents(): array
    {
        $tabs = collect($this->tabs())
            ->map(fn (array $sections, string $handle) => [
                'display' => Str::slugToTitle($handle),
                'sections' => $this->sections($sections),
            ])
            ->pipe($this->resolveLazyValues(...))
            ->all();

        return ['tabs' => $tabs];
    }

    protected function sections(array $sections): array
    {
        return collect($sections)
            ->map(fn (array $section) => [
                ...$section,
                'fields' => collect($section['fields'])
                    ->filter(fn (array $field) => ! isset($field['field']['feature']) ||
                        $field['field']['feature']::enabled($this->context)
                    )
                    ->all(),
            ])
            ->filter(fn (array $section) => $section['fields'])
            ->values()
            ->all();
    }

    protected function lazy(callable $callback, mixed $fallback = null): Closure
    {
        return fn (?Context $context) => $context ? $callback($context) : $fallback;
    }

    protected function resolveLazyValues(Collection $tabs): Collection
    {
        return $tabs->map(fn ($value) => $this->resolveValue($value));
    }

    protected function resolveValue(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return $value($this->context);
        }

        if (is_array($value) || $value instanceof Collection) {
            return collect($value)->map(fn ($item) => $this->resolveValue($item))->all();
        }

        return $value;
    }

    protected function trans(string $key, array $placeholders = []): ?string
    {
        if (! $this->context) {
            return null;
        }

        return __("advanced-seo::fields.$key", [
            'type' => $this->contentTypeLabel(),
            'content' => $this->contentLabel(),
            ...$placeholders,
        ]);
    }

    /**
     * Scope-aware label: "collection"/"taxonomy" for config, "entries"/"terms" for localizations, "entry"/"term" for content.
     */
    protected function contentTypeLabel(): string
    {
        return match ([$this->context?->scope, $this->context?->type]) {
            [Scope::CONFIG, 'collections'] => __('collection'),
            [Scope::CONFIG, 'taxonomies'] => __('taxonomy'),
            [Scope::LOCALIZATION, 'collections'] => $this->lcfirst(__('advanced-seo::messages.entries')),
            [Scope::LOCALIZATION, 'taxonomies'] => $this->lcfirst(__('advanced-seo::messages.terms')),
            [Scope::CONTENT, 'collections'] => $this->lcfirst(__('advanced-seo::messages.entry')),
            [Scope::CONTENT, 'taxonomies'] => $this->lcfirst(__('advanced-seo::messages.term')),
            default => '',
        };
    }

    /**
     * The content items label: "entries" or "terms".
     */
    protected function contentLabel(): string
    {
        return match ($this->context?->type) {
            'collections' => $this->lcfirst(__('advanced-seo::messages.entries')),
            'taxonomies' => $this->lcfirst(__('advanced-seo::messages.terms')),
            default => '',
        };
    }

    /**
     * Lowercase the first character, except for languages like German where nouns are always capitalized.
     */
    protected function lcfirst(string $value): string
    {
        return str_starts_with(app()->getLocale(), 'de') ? $value : lcfirst($value);
    }
}
