<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Contracts\Blueprint as Contract;
use Statamic\Facades\Blueprint;

class BaseBlueprint implements Contract
{
    protected mixed $data;
    protected array $sections;

    public function for(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function contents(): array
    {
        return Blueprint::makeFromSections($this->sections())->contents();
    }

    protected function sections(): array
    {
        return collect($this->sections)->map(function ($section) {
            return resolve($section)->for($this->data)->contents();
        })->filter()->all();
    }
}
