<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Concerns\ManagesSeoSetLocalizations;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
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
            RemoveSeoValues::handle($seoSet->parent());
        }
    }

    /**
     * When the sitemap is disabled in the config,
     * remove the per-entry/term sitemap values.
     */
    protected function cleanupSitemapValues(SeoSetConfig $config): void
    {
        if ($config->value('sitemap')) {
            return;
        }

        RemoveSeoValues::handle($config->seoSet()->parent(), [
            'seo_sitemap_enabled',
            'seo_sitemap_priority',
            'seo_sitemap_change_frequency',
        ]);
    }

    /**
     * When the social images generator is disabled in the config,
     * remove the per-entry/term toggle and theme values.
     */
    protected function cleanupSocialImageGeneratorValues(SeoSetConfig $config): void
    {
        if ($config->value('social_images_generator')) {
            return;
        }

        RemoveSeoValues::handle($config->seoSet()->parent(), [
            'seo_generate_social_images',
            'seo_social_images_theme',
        ]);
    }
}
