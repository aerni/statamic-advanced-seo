<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Facades\Fieldset;
use Illuminate\Support\Collection;

class SeoEntryFieldset extends BaseFieldset
{
    protected string $display = 'Seo';

    protected function sections(): array
    {
        return [
            $this->entrySeo(),
        ];
    }

    protected function entrySeo(): Collection
    {
        return Fieldset::find('entry_seo');
    }
}
