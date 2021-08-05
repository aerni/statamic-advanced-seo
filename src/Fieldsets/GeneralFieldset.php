<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Contracts\Fieldset;
use Aerni\AdvancedSeo\Facades\SeoGlobals;
use Illuminate\Support\Collection;

class GeneralFieldset implements Fieldset
{
    public function contents(): ?array
    {
        if ($this->fields()->isEmpty()) {
            return null;
        }

        return [
            'display' => 'General',
            'fields' => $this->fields(),
        ];
    }

    public function fields(): Collection
    {
        return collect()
            ->merge($this->general());
    }

    protected function general(): Collection
    {
        return SeoGlobals::fieldset('general');
    }
}
