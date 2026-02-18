<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Concerns\ManagesSeoSetLocalizations;
use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;

class HandleSeoSetConfigDeleted
{
    use ManagesSeoSetLocalizations;

    public function handle(SeoSetConfigDeleted $event): void
    {
        $seoSet = $event->config->seoSet();

        $this->deleteLocalizations($seoSet);
        RemoveSeoValues::handle($seoSet->parent());
    }
}
