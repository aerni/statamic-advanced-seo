<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Contracts\Fieldset as Contract;
use Illuminate\Support\Collection;

abstract class BaseFieldset implements Contract
{
    protected string $display;

    public function contents(): ?array
    {
        if ($this->fields()->isEmpty()) {
            return null;
        }

        return [
            'display' => $this->display(),
            'fields' => $this->fields(),
        ];
    }

    protected function fields(): Collection
    {
        return collect($this->sections())->collapse();
    }

    protected function display(): string
    {
        return $this->display;
    }

    abstract protected function sections(): array;
}
