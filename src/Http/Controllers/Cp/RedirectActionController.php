<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Http\Controllers\CP\ActionController;

class RedirectActionController extends ActionController
{
    protected function getSelectedItems($items, $context)
    {
        return $items->map(fn ($id) => Redirects::find($id))->filter();
    }
}
