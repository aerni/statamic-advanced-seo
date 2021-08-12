<?php

namespace Aerni\AdvancedSeo\Tags;

use Statamic\Tags\Tags;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'advanced_seo';

    public function head()
    {
        return view('advanced-seo::head', $this->context)->render();
    }
}
