<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Actions\EvaluateFeature;
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
        $blueprint = Blueprint::make()
            ->setHandle($this->handle())
            ->setContents(['tabs' => $this->processedTabs()]);

        return $this->removeDisabledFeatureFields($blueprint);
    }

    protected function removeDisabledFeatureFields(BlueprintFields $blueprint): BlueprintFields
    {
        if (! isset($this->data)) {
            return $blueprint;
        }

        $blueprint->fields()->all()
            ->filter(fn ($field) => $field->get('feature'))
            ->filter(fn ($field) => ! EvaluateFeature::handle($field->get('feature'), $this->data))
            ->each(fn ($field) => $blueprint->removeField($field->handle()));

        return $blueprint;
    }

    public function items(): array
    {
        return $this->get()->fields()->all()->mapWithKeys(function ($field, $handle) {
            return [$handle => $field->config()];
        })->toArray();
    }

    protected function processedTabs(): array
    {
        return collect($this->tabs())->map(function ($tab, $handle) {
            return [
                'display' => Str::slugToTitle($handle),
                'sections' => isset($this->data)
                    ? $tab::make()->data($this->data)->get()
                    : $tab::make()->get(),
            ];
        })
        ->filter(fn ($tab) => ! empty($tab['sections']))
        ->all();
    }

    abstract protected function tabs(): array;

    abstract protected function handle(): string;
}
