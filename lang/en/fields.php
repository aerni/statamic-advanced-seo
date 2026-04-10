<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Fields
    |--------------------------------------------------------------------------
    |
    | Used by ContentSeoBlueprint and ContentSeoSetLocalizationBlueprint.
    |
    */

    'seo_section_search_appearance' => [
        'display' => 'Search Appearance',
        'instructions' => 'Control how this :type appears in search results.',
        'default_instructions' => 'Set defaults for how your :type appear in search results.',
    ],

    'seo_title' => [
        'display' => 'Meta Title',
        'instructions' => 'Shown as the title in search engine results.',
        'default_instructions' => 'The default title for your :type in search results.',
    ],

    'seo_description' => [
        'display' => 'Meta Description',
        'instructions' => 'Shown as the description in search engine results.',
        'default_instructions' => 'The default description for your :type in search results.',
    ],

    'seo_search_preview' => [
        'display' => 'Search Preview',
    ],

    'seo_section_social_appearance' => [
        'display' => 'Social Appearance',
        'instructions' => 'Control how this :type looks when shared on social media.',
        'default_instructions' => 'Set defaults for how your :type look when shared on social media.',
    ],

    'seo_og_title' => [
        'display' => 'Social Title',
        'instructions' => 'Shown as the title when sharing on social media.',
        'default_instructions' => 'The default social title for your :type.',
    ],

    'seo_og_description' => [
        'display' => 'Social Description',
        'instructions' => 'Shown as the description when sharing on social media.',
        'default_instructions' => 'The default social description for your :type.',
    ],

    'seo_og_image' => [
        'display' => 'Social Image',
        'instructions' => 'Recommended size: 1200x630px. Automatically resized as needed.',
        'default_instructions' => 'The default social image for your :type. Recommended size: 1200x630px.',
    ],

    'seo_generate_social_images' => [
        'display' => 'Generate Social Images',
        'instructions' => 'Automatically generate social images for this :type.',
        'default_instructions' => 'Automatically generate social images for your :type.',
    ],

    'seo_social_images_theme' => [
        'display' => 'Theme',
        'instructions' => 'The theme used for generated social images.',
        'default_instructions' => 'The default theme for generated social images.',
    ],

    'seo_social_preview' => [
        'display' => 'Social Preview',
    ],

    'seo_section_indexing' => [
        'display' => 'Indexing',
        'instructions' => 'Control search engine indexing for this :type.',
        'default_instructions' => 'Set default indexing behavior for your :type.',
    ],

    'seo_noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Prevent search engines from indexing this :type.',
        'default_instructions' => 'Prevent search engines from indexing your :type.',
    ],

    'seo_nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Prevent crawlers from following links on this :type.',
        'default_instructions' => 'Prevent crawlers from following links on your :type.',
    ],

    'seo_section_canonical_url' => [
        'display' => 'Canonical URL',
        'instructions' => 'Set the canonical URL for this :type.',
        'default_instructions' => 'Set the default canonical URL for your :type.',
    ],

    'seo_canonical_type' => [
        'display' => 'Canonical URL',
        'instructions' => 'Where the canonical URL should point to.',
        'default_instructions' => 'Where the default canonical URL should point to.',
        'current' => 'Current :type',
        'other' => 'Other Entry',
        'custom' => 'Custom URL',
    ],

    'seo_canonical_entry' => [
        'display' => 'Entry',
        'instructions' => 'The entry with the original content.',
        'default_instructions' => 'The entry with the original content.',
    ],

    'seo_canonical_custom' => [
        'display' => 'URL',
        'instructions' => 'A fully qualified [active URL](https://laravel.com/docs/master/validation#rule-active-url).',
        'default_instructions' => 'A fully qualified [active URL](https://laravel.com/docs/master/validation#rule-active-url).',
    ],

    'seo_section_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Configure sitemap settings for this :type.',
        'default_instructions' => 'Set default sitemap settings for your :type.',
    ],

    'seo_sitemap_priority' => [
        'display' => 'Priority',
        'instructions' => 'Sitemap priority. 1.0 is the most important.',
        'default_instructions' => 'Default sitemap priority. 1.0 is the most important.',
    ],

    'seo_sitemap_change_frequency' => [
        'display' => 'Change Frequency',
        'instructions' => 'How often search engines should crawl this :type.',
        'default_instructions' => 'How often search engines should crawl your :type.',
        'always' => 'Always',
        'hourly' => 'Hourly',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'never' => 'Never',
    ],

    'seo_section_structured_data' => [
        'display' => 'Structured Data',
        'instructions' => 'Add custom [structured data](https://developers.google.com/search/docs/guides/intro-structured-data) for this :type.',
        'default_instructions' => 'Set default [structured data](https://developers.google.com/search/docs/guides/intro-structured-data) for your :type.',
    ],

    'seo_json_ld' => [
        'display' => 'JSON-LD Schema',
        'instructions' => 'Custom structured data. Automatically wrapped in a script tag.',
        'default_instructions' => 'Default structured data for your :type. Automatically wrapped in a script tag.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — General
    |--------------------------------------------------------------------------
    |
    | Used by SiteSeoSetLocalizationBlueprint.
    |
    */

    'section_titles' => [
        'display' => 'Titles',
        'instructions' => 'Configure how page titles are displayed.',
    ],

    'site_name' => [
        'display' => 'Site Name',
        'instructions' => 'Appended to your meta titles.',
    ],

    'separator' => [
        'display' => 'Separator',
        'instructions' => 'The character between the page title and site name.',
    ],

    'section_favicon' => [
        'display' => 'Favicon',
        'instructions' => 'The icon displayed in browser tabs.',
    ],

    'favicon_svg' => [
        'display' => 'Favicon (SVG)',
        'instructions' => 'Upload an SVG favicon.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — Search Appearance
    |--------------------------------------------------------------------------
    */

    'section_structured_data' => [
        'display' => 'Structured Data',
        'instructions' => 'Help search engines understand your site with [structured data](https://developers.google.com/search/docs/guides/intro-structured-data).',
    ],

    'site_json_ld_type' => [
        'display' => 'Content Type',
        'instructions' => 'What this site represents.',
        'none' => 'None',
        'organization' => 'Organization',
        'person' => 'Person',
        'custom' => 'Custom',
    ],

    'use_breadcrumbs' => [
        'display' => 'Breadcrumbs',
        'instructions' => 'Add [breadcrumb](https://developers.google.com/search/docs/data-types/breadcrumb) structured data.',
    ],

    'organization_name' => [
        'display' => 'Organization Name',
        'instructions' => 'The name of the organization.',
    ],

    'organization_logo' => [
        'display' => 'Organization Logo',
        'instructions' => 'Minimum size: 112x112px.',
    ],

    'person_name' => [
        'display' => 'Person Name',
        'instructions' => 'The name of the person.',
    ],

    'site_json_ld' => [
        'display' => 'JSON-LD Schema',
        'instructions' => 'Structured data added to every page. Automatically wrapped in a script tag.',
    ],

    'section_indexing' => [
        'display' => 'Indexing',
        'instructions' => 'Site-wide indexing settings. These override entry and term settings.',
    ],

    'noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Prevent search engines from indexing your entire site.',
    ],

    'nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Prevent crawlers from following any links on your site.',
    ],

    'section_verification' => [
        'display' => 'Site Verification',
        'instructions' => 'Verify ownership of this site.',
    ],

    'google_site_verification_code' => [
        'display' => 'Google Verification Code',
        'instructions' => 'From [Google Search Console](https://search.google.com/search-console).',
    ],

    'bing_site_verification_code' => [
        'display' => 'Bing Verification Code',
        'instructions' => 'From [Bing Webmaster Tools](https://www.bing.com/toolbox/webmaster).',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — Social Appearance
    |--------------------------------------------------------------------------
    */

    'section_social_image' => [
        'display' => 'Social Image',
        'instructions' => 'Used as the default when sharing links on social media.',
    ],

    'og_image' => [
        'display' => 'Social Image',
        'instructions' => 'Fallback image when none is set on the content. Recommended size: 1200x630px.',
    ],

    'section_twitter' => [
        'display' => 'X (Twitter)',
        'instructions' => 'Configure how links to your site appear on X.',
    ],

    'twitter_card' => [
        'display' => 'X (Twitter) Card',
        'instructions' => 'The size of the link preview on X.',
        'summary' => 'Small',
        'summary_large_image' => 'Large',
    ],

    'twitter_handle' => [
        'display' => 'X (Twitter) Username',
        'instructions' => 'The X account associated with this site.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — Analytics
    |--------------------------------------------------------------------------
    */

    'section_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'A privacy-friendly alternative to Google Analytics. [Learn more](https://usefathom.com)',
    ],

    'use_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'Add the Fathom tracking script.',
    ],

    'fathom_id' => [
        'display' => 'Site ID',
        'instructions' => 'Your Fathom site ID.',
    ],

    'fathom_spa' => [
        'display' => 'SPA Mode',
        'instructions' => 'Enable for single page applications.',
    ],

    'section_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'A privacy-friendly alternative to Google Analytics. [Learn more](https://www.cloudflare.com/web-analytics)',
    ],

    'use_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'Add the Cloudflare Web Analytics tracking script.',
    ],

    'cloudflare_web_analytics' => [
        'display' => 'Beacon Token',
        'instructions' => 'Your Cloudflare beacon token.',
    ],

    'section_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Manage tracking scripts via [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager).',
    ],

    'use_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Add the Google Tag Manager scripts.',
    ],

    'google_tag_manager' => [
        'display' => 'Container ID',
        'instructions' => 'Your GTM container ID.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — AI
    |--------------------------------------------------------------------------
    */

    'section_ai' => [
        'display' => 'AI',
        'instructions' => 'Configure AI features for your site.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Config Fields
    |--------------------------------------------------------------------------
    |
    | Used by ContentSeoSetConfigBlueprint and SiteSeoSetConfigBlueprint.
    |
    */

    'config_enabled' => [
        'display' => 'Enable SEO',
        'instructions' => 'Process and output SEO data for this :type.',
    ],

    'config_editable' => [
        'display' => 'Enable Editing',
        'instructions' => 'Allow editing of SEO fields on individual :content.',
    ],

    'config_section_sitemaps' => [
        'display' => 'Sitemaps',
    ],

    'config_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Enables the sitemap for this :type.',
    ],

    'config_section_social_images' => [
        'display' => 'Social Images',
    ],

    'config_social_images_generator' => [
        'display' => 'Social Images Generator',
        'instructions' => 'Enables the social images generator for this :type.',
    ],

    'config_social_images_themes' => [
        'display' => 'Themes',
        'instructions' => 'Select the social image themes available for this :type.',
    ],

    'config_section_ai' => [
        'display' => 'AI',
    ],

    'config_ai' => [
        'display' => 'Enable AI',
        'instructions' => 'Enables AI features for this :type.',
    ],

    'config_ai_instructions' => [
        'display' => 'Copywriting Instructions',
        'instructions' => 'Specific instructions for generating titles and descriptions for this :type.',
        'placeholder' => 'Example: The :content of this :type are products. Highlight features and pricing.',
    ],

    'config_ai_instructions_site' => [
        'display' => 'Copywriting Instructions',
        'instructions' => 'General instructions for generating titles and descriptions across all collections and taxonomies.',
        'placeholder' => 'Example: Use a friendly, conversational tone. Always say "sustainable" instead of "eco-friendly".',
    ],

];
