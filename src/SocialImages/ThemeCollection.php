<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\SocialImages\Theme;

class ThemeCollection extends Collection
{
    public function options(): array
    {
        return $this->pluck('title', 'handle')->all();
    }

    public function default(): ?Theme
    {
        return $this->firstWhere('handle', 'default') ?? $this->first();
    }
}
