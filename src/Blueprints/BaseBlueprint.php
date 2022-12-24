<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Contracts\Blueprint as Contract;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as BlueprintFields;
use Statamic\Support\Str;

abstract class BaseBlueprint implements Contract
{
    protected DefaultsData $data;

    public static function make(): self
    {
        return new static();
    }

    public function data(DefaultsData $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function get(): BlueprintFields
    {
        return Blueprint::make()
            ->setHandle($this->handle())
            ->setContents(['sections' => $this->processSections()]);
    }

    public function enabledFeatureFields(): array
    {
        return collect($this->items())->filter(function ($field) {
            $enabledFeature = $field['enabled_feature'] ?? null;

            // Fields that are not linked to a feature should always be part of the blueprint
            if (is_null($enabledFeature)) {
                return true;
            }

            return $enabledFeature;
        })->toArray();
    }

    public function items(): array
    {
        return $this->get()->fields()->all()->mapWithKeys(function ($field, $handle) {
            return [$handle => $field->config()];
        })->toArray();
    }

    protected function processSections(): array
    {
        return collect($this->sections())->map(function ($section, $handle) {
            return [
                'display' => Str::slugToTitle($handle),
                'fields' => isset($this->data) ? $section::make()->data($this->data)->get() : $section::make()->get(),
            ];
        })->filter(function ($section) {
            return ! empty($section['fields']);
        })->all();
    }

    abstract protected function sections(): array;

    abstract protected function handle(): string;
}
