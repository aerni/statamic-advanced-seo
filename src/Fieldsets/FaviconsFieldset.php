<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Facades\Fieldset;
use Illuminate\Support\Collection;

class FaviconsFieldset extends BaseFieldset
{
    protected string $display = 'Favicons';

    protected function sections(): array
    {
        return [
            $this->favicons(),
        ];
    }

    protected function favicons(): ?Collection
    {
        return config('advanced-seo.favicons', true)
            ? Fieldset::find('favicons')
            : null;
    }
}
