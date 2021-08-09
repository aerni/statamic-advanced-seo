<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\FieldsetRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Fieldset;

class FieldsetRepository implements Contract
{
    public function find(string $handle): Collection
    {
        return Fieldset::setDirectory(__DIR__ . '/../../resources/fieldsets/')
            ->find($handle)
            ->fields()
            ->all()
            ->map(function ($field) {
                return $field->config();
            });
    }
}
