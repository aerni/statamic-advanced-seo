<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\IndexingFields;

class IndexingBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'indexing';
    }

    protected function tabs(): array
    {
        return [
            'indexing' => IndexingFields::class,
        ];
    }
}
