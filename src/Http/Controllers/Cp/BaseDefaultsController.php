<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

abstract class BaseDefaultsController extends CpController
{
    abstract public function edit(Request $request, string $handle);

    abstract public function update(string $handle, Request $request);

    abstract protected function repository(string $handle);

    protected function extractFromFields($set, $blueprint)
    {
        $fields = $blueprint
            ->fields()
            ->addValues($set->values()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }
}
