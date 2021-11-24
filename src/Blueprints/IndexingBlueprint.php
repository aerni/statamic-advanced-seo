<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\IndexingFields;

class IndexingBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'indexing';
    }

    protected function sections(): array
    {
        return [
            'indexing' => IndexingFields::class,
        ];
    }
}
