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
        return new static;
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
                'sections' => isset($this->data) ? $tab::make()->data($this->data)->get() : $tab::make()->get(),
            ])
            ->filter(fn ($tab) => ! empty($tab['sections']))
            ->all();
    }

    abstract protected function tabs(): array;

    abstract protected function handle(): string;
}
