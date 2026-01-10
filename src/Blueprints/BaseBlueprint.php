<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Contracts\Blueprint as Contract;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as BlueprintFields;
use Statamic\Support\Str;

abstract class BaseBlueprint implements Contract
{
    protected mixed $model = null;

    public static function make(): static
    {
        return new static;
    }

    public static function resolve(mixed $model = null): BlueprintFields
    {
        return static::make()->for($model)->get();
    }

    public function for(mixed $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function get(): BlueprintFields
    {
        return Blueprint::make()
            ->setHandle($this->handle())
            ->setContents(['tabs' => $this->processedTabs()]);
    }

    public function items(): array
    {
        return $this->get()->fields()->all()
            ->mapWithKeys(fn ($field, $handle) => [$handle => $field->config()])
            ->toArray();
    }

    protected function processedTabs(): array
    {
        return collect($this->tabs())
            ->map(fn ($tab, $handle) => [
                'display' => Str::slugToTitle($handle),
                'sections' => $tab::resolve($this->model),
            ])
            ->filter(fn ($tab) => ! empty($tab['sections']))
            ->all();
    }

    abstract protected function tabs(): array;

    abstract protected function handle(): string;
}
