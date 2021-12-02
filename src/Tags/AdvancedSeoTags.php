<?php

namespace Aerni\AdvancedSeo\Tags;

use Illuminate\View\View;
use Statamic\Tags\Tags;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'seo';

    public function head(): View
    {
        return view('advanced-seo::head');
    }

    public function body(): View
    {
        return view('advanced-seo::body');
    }
}
