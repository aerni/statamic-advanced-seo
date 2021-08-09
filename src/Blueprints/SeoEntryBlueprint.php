<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Blueprints\BaseBlueprint;
use Aerni\AdvancedSeo\Fieldsets\SeoEntryFieldset;

class SeoEntryBlueprint extends BaseBlueprint
{
    protected array $sections = [
        'seo' => SeoEntryFieldset::class,
    ];
}
