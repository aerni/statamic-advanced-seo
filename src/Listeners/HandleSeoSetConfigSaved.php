<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;
use Aerni\AdvancedSeo\Listeners\Concerns\ManagesSeoSetLocalizations;

class HandleSeoSetConfigSaved
{
    use ManagesSeoSetLocalizations;

    public function handle(SeoSetConfigSaved $event): void
    {
        $seoSet = $event->config->seoSet();

        if ($seoSet->enabled()) {
            $this->saveExistingLocalizations($seoSet);
            $this->cleanupOrphanedLocalizations($seoSet);
        } else {
            $this->deleteLocalizations($seoSet);
            $this->cleanupSeoValues($seoSet);
        }
    }
}
