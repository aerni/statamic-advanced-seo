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
        $docs = 'https://advanced-seo.michaelaerni.ch';

        return [
            ['title' => __('advanced-seo::messages.pro_feature_sitemaps'), 'icon' => 'hierarchy', 'url' => "$docs/usage/sitemaps"],
            ['title' => __('advanced-seo::messages.pro_feature_multi_site'), 'icon' => 'earth'],
            ['title' => __('advanced-seo::messages.pro_feature_ai'), 'icon' => 'ai-spark', 'url' => "$docs/usage/on-page-seo#ai-content-generation"],
            ['title' => __('advanced-seo::messages.pro_feature_social_images'), 'icon' => 'media-image-picture-gallery', 'url' => "$docs/usage/social-images-generator"],
            ['title' => __('advanced-seo::messages.pro_feature_permissions'), 'icon' => 'permissions', 'url' => "$docs/usage/permissions"],
            ['title' => __('advanced-seo::messages.pro_feature_custom_routes'), 'icon' => 'arrow-roadmap-path-flow', 'url' => "$docs/extending/custom-routes"],
            ['title' => __('advanced-seo::messages.pro_feature_custom_tokens'), 'icon' => 'programming-script-code-brackets', 'url' => "$docs/extending/tokens"],
            ['title' => __('advanced-seo::messages.pro_feature_graphql'), 'icon' => 'graphql', 'url' => "$docs/reference/graphql"],
            ['title' => __('advanced-seo::messages.pro_feature_eloquent'), 'icon' => 'fieldtype-grid', 'url' => "$docs/usage/configuration#eloquent-driver"],
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
