<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\FieldsetRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Fieldset;

class FieldsetRepository implements Contract
{
    public function find(string $handle): ?Collection
    {
        $fieldset = Fieldset::setDirectory(resource_path('fieldsets'))->find($handle)
            ?? Fieldset::setDirectory(__DIR__ . '/../../resources/fieldsets/')->find($handle);

        if (! $fieldset) {
            return null;
        }

        return $fieldset->fields()->all()->map(function ($field) {
            return $field->config();
        });
    }
}
