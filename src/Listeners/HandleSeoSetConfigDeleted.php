<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;
use Aerni\AdvancedSeo\Listeners\Concerns\ManagesSeoSetLocalizations;

class HandleSeoSetConfigDeleted
{
    use ManagesSeoSetLocalizations;

    public function handle(SeoSetConfigDeleted $event): void
    {
        $seoSet = $event->config->seoSet();

        $this->deleteLocalizations($seoSet);
        $this->cleanupSeoValues($seoSet);
    }
}
