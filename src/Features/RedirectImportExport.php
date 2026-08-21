<?php

namespace Aerni\AdvancedSeo\Features;

use Statamic\Console\Processes\Composer;

class RedirectImportExport extends Feature
{
    protected static function available(): bool
    {
        return Redirects::available()
            && app(Composer::class)->isInstalled('spatie/simple-excel');
    }
}
