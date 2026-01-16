<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Closure;
use Statamic\Support\Str;
use Statamic\Fields\Blueprint;
use Aerni\AdvancedSeo\Enums\Scope;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Actions\EvaluateFeature;

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
        if (! $this->context) {
            return $sections;
        }

        return collect($sections)
            ->map(fn (array $section) => [
                ...$section,
                'fields' => collect($section['fields'])
                    ->filter(fn (array $field) => ! isset($field['field']['feature']) ||
                        EvaluateFeature::handle($field['field']['feature'], $this->context)
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
        return $tabs->dot()
            ->map(fn ($value) => $value instanceof Closure ? $value($this->context) : $value)
            ->undot();
    }

    protected function trans(string $key, array $placeholders = []): ?string
    {
        if (! $this->context) {
            return null;
        }

        $placeholders = array_merge(['type' => $this->contentTypeLabel()], $placeholders);

        return __("advanced-seo::fields.$key", $placeholders);
    }

    protected function contentTypeLabel(): string
    {
        return match ([$this->context?->scope, $this->context?->type]) {
            [Scope::CONFIG, 'collections'] => __('collection'),
            [Scope::CONFIG, 'taxonomies'] => __('taxonomy'),
            [Scope::LOCALIZATION, 'collections'] => lcfirst(__('advanced-seo::messages.entries')),
            [Scope::LOCALIZATION, 'taxonomies'] => lcfirst(__('advanced-seo::messages.terms')),
            [Scope::CONTENT, 'collections'] => lcfirst(__('advanced-seo::messages.entry')),
            [Scope::CONTENT, 'taxonomies'] => lcfirst(__('advanced-seo::messages.term')),
            default => '',
        };
    }
}
