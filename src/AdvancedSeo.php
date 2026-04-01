<?php

namespace Aerni\AdvancedSeo;

use Statamic\Licensing\LicenseManager;

class AdvancedSeo
{
    /**
     * Whether the addon is running the pro edition.
     */
    public static function pro(): bool
    {
        return static::edition() === 'pro';
    }

    /**
     * Whether to show pro upgrade prompts in the CP.
     * Only shown to free users on test domains.
     */
    public static function shouldPromoteUpgrade(): bool
    {
        return ! static::pro() && app(LicenseManager::class)->isOnTestDomain();
    }

    /**
     * The list of pro features for upgrade prompts.
     */
    public static function proFeatures(): array
    {
        return [
            ['title' => __('advanced-seo::messages.pro_feature_sitemaps'), 'icon' => 'hierarchy'],
            ['title' => __('advanced-seo::messages.pro_feature_multi_site'), 'icon' => 'earth'],
            ['title' => __('advanced-seo::messages.pro_feature_ai'), 'icon' => 'ai-spark'],
            ['title' => __('advanced-seo::messages.pro_feature_social_images'), 'icon' => 'media-image-picture-gallery'],
            ['title' => __('advanced-seo::messages.pro_feature_permissions'), 'icon' => 'permissions'],
            ['title' => __('advanced-seo::messages.pro_feature_custom_routes'), 'icon' => 'arrow-roadmap-path-flow'],
            ['title' => __('advanced-seo::messages.pro_feature_custom_tokens'), 'icon' => 'programming-script-code-brackets'],
            ['title' => __('advanced-seo::messages.pro_feature_graphql'), 'icon' => 'graphql'],
            ['title' => __('advanced-seo::messages.pro_feature_eloquent'), 'icon' => 'fieldtype-grid'],
        ];
    }

    /**
     * The configured edition of the addon.
     * Reads config directly instead of Addon::get()->edition()
     * so it can be called during service provider registration.
     */
    public static function edition(): string
    {
        return config('statamic.editions.addons.aerni/advanced-seo', 'free');
    }
}
