<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Concerns\ManagesSeoSetLocalizations;
use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;

class HandleSeoSetConfigSaved
{
    use ManagesSeoSetLocalizations;

    public function handle(SeoSetConfigSaved $event): void
    {
        $seoSet = $event->config->seoSet();

        if ($seoSet->enabled()) {
            $this->saveExistingLocalizations($seoSet);
            $this->cleanupOrphanedLocalizations($seoSet);
            $this->cleanupSitemapValues($event->config);
            $this->cleanupSocialImageGeneratorValues($event->config);
        } else {
            $this->deleteLocalizations($seoSet);
            $this->cleanupSeoValues($seoSet);
        }
    }
}
