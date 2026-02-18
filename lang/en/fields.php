<?php

return [

    'seo_section_search_appearance' => [
        'display' => 'Search Appearance',
        'instructions' => 'Configure how this :type appears in search results.',
        'default_instructions' => 'Configure how your :type appear in search results by default.',
    ],

    'seo_title' => [
        'display' => 'Meta Title',
        'instructions' => 'Set the meta title of this :type.',
        'default_instructions' => 'Set the default meta title of your :type.',
    ],

    'seo_description' => [
        'display' => 'Meta Description',
        'instructions' => 'Set the meta description of this :type.',
        'default_instructions' => 'Set the default meta description of your :type.',
    ],

    'seo_site_name_position' => [
        'display' => 'Site Name Position',
        'instructions' => 'Set the site name position for the meta title of this :type.',
        'default_instructions' => 'Set the default site name position for the meta title of your :type.',
        'end' => 'End',
        'start' => 'Start',
        'disabled' => 'Disabled',
    ],

    'seo_section_social_appearance' => [
        'display' => 'Social Appearance',
        'instructions' => 'Configure how this :type appears when shared on social media.',
        'default_instructions' => 'Configure how your :type appear when shared on social media by default.',
    ],

    'seo_generate_social_images' => [
        'display' => 'Generate Social Images',
        'instructions' => 'Activate to use the social images generator for this :type.',
        'default_instructions' => 'Activate to use the social images generator for your :type by default.',
    ],

    'seo_social_images_theme' => [
        'display' => 'Theme',
        'instructions' => 'Choose the social images theme for this :type.',
        'default_instructions' => 'Choose the default social images theme for your :type.',
    ],

    'seo_og_image' => [
        'display' => 'Social Image',
        'instructions' => 'The recommended size is 1200x630px. The image is automatically resized as needed.',
        'default_instructions' => 'Add a default social image for your :type. The image is automatically resized for each platform. Recommended size is 1200x630px.',
    ],

    'seo_og_title' => [
        'display' => 'Social Title',
        'instructions' => 'Set the social title of this :type.',
        'default_instructions' => 'Set the default social title of your :type.',
    ],

    'seo_og_description' => [
        'display' => 'Social Description',
        'instructions' => 'Set the social description of this :type.',
        'default_instructions' => 'Set the default social description of your :type.',
    ],

    'seo_section_canonical_url' => [
        'display' => 'Canonical URL',
        'instructions' => 'Configure the canonical URL settings of this :type.',
        'default_instructions' => 'Configure the default canonical URL settings of your :type.',
    ],

    'seo_canonical_type' => [
        'display' => 'Canonical URL',
        'instructions' => 'Where should the canonical URL of this :type point to.',
        'default_instructions' => 'Where should the default canonical URL of your :type point to.',
        'current' => 'Current :type',
        'other' => 'Other Entry',
        'custom' => 'Custom URL',
    ],

    'seo_canonical_entry' => [
        'display' => 'Entry',
        'instructions' => 'Choose the entry with the original content.',
        'default_instructions' => 'Choose the entry with the original content.',
    ],

    'seo_canonical_custom' => [
        'display' => 'URL',
        'instructions' => 'A fully qualified and [active URL](https://laravel.com/docs/11.x/validation#rule-active-url).',
        'default_instructions' => 'A fully qualified and [active URL](https://laravel.com/docs/11.x/validation#rule-active-url).',
    ],

    'seo_section_indexing' => [
        'display' => 'Indexing',
        'instructions' => 'Configure the indexing settings of this :type.',
        'default_instructions' => 'Configure the default indexing settings of your :type.',
    ],

    'seo_noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Prevent this :type from being indexed by search engines.',
        'default_instructions' => 'Prevent your :type from being indexed by search engines.',
    ],

    'seo_nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Prevent site crawlers from following links on this :type\'s page.',
        'default_instructions' => 'Prevent site crawlers from following links on your :type\' pages.',
    ],

    'seo_section_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Configure the sitemap settings of this :type.',
        'default_instructions' => 'Configure the default sitemap settings of your :type.',
    ],

    'seo_sitemap_enabled' => [
        'display' => 'Enabled',
        'instructions' => 'Choose to add or remove this :type from the sitemap.',
        'default_instructions' => 'Choose to add or remove your :type from the sitemap.',
    ],

    'seo_sitemap_priority' => [
        'display' => 'Priority',
        'instructions' => 'Choose the sitemap priority of this :type. 1.0 is the most important.',
        'default_instructions' => 'Choose the default sitemap priority of your :type. 1.0 is the most important.',
    ],

    'seo_sitemap_change_frequency' => [
        'display' => 'Change Frequency',
        'instructions' => 'Choose the frequency in which search engines should crawl this :type.',
        'default_instructions' => 'Choose the default frequency in which search engines should crawl your :type.',
        'always' => 'Always',
        'hourly' => 'Hourly',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'never' => 'Never',
    ],

    'seo_section_json_ld' => [
        'display' => 'JSON-ld Schema',
        'instructions' => 'Add custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for this :type.',
        'default_instructions' => 'Add default custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for your :type.',
    ],

    'seo_json_ld' => [
        'display' => 'JSON-LD Schema',
        'instructions' => 'The structured data of this :type. This will be wrapped in the appropriate script tag.',
        'default_instructions' => 'The default structured data of your :type. This will be wrapped in the appropriate script tag.',
    ],

    'seo_search_preview' => [
        'display' => 'Search Preview',
    ],

    'seo_social_preview' => [
        'display' => 'Social Preview',
    ],

    'section_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'Use [Fathom](https://usefathom.com) as a privacy-friendly alternative to Google Analytics.',
    ],

    'use_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'Add the Fathom tracking script to your head.',
    ],

    'fathom_id' => [
        'display' => 'Site ID',
        'instructions' => 'Add your site ID.',
    ],

    'fathom_spa' => [
        'display' => 'SPA Mode',
        'instructions' => 'Activate if your site is a single page application.',
    ],

    'section_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'Use [Cloudflare Web Analytics](https://www.cloudflare.com/web-analytics) as a privacy-friendly alternative to Google Analytics.',
    ],

    'use_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'Add the Cloudflare tracking script to your head.',
    ],

    'cloudflare_web_analytics' => [
        'display' => 'Beacon Token',
        'instructions' => 'Add your beacon token.',
    ],

    'section_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Use [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager) to track your users. You are `required by privacy law` to get your user\'s consent before loading any tracking scripts. You also need to inform them about what data you collect and what you intent to do with it.',
    ],

    'use_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Add the Google Tag Manager tracking scripts.',
    ],

    'google_tag_manager' => [
        'display' => 'Container ID',
        'instructions' => 'Add your container ID.',
    ],

    'section_favicon' => [
        'display' => 'Favicon',
        'instructions' => 'Configure the favicon of your site.',
    ],

    'favicon_svg' => [
        'display' => 'Favicon (SVG)',
        'instructions' => 'Add your favicon as SVG file.',
    ],

    'section_titles' => [
        'display' => 'Titles',
        'instructions' => 'Configure the appearance of your page titles.',
    ],

    'site_name' => [
        'display' => 'Site Name',
        'instructions' => 'The site name is added to your meta titles.',
    ],

    'title_separator' => [
        'display' => 'Title Separator',
        'instructions' => 'This separates the site name and page title.',
    ],

    'section_knowledge_graph' => [
        'display' => 'Basic Information',
        'instructions' => 'Add basic [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) information about this site.',
    ],

    'site_json_ld_type' => [
        'display' => 'Content Type',
        'instructions' => 'The type of content this site represents.',
        'none' => 'None',
        'organization' => 'Organization',
        'person' => 'Person',
        'custom' => 'Custom',
    ],

    'organization_name' => [
        'display' => 'Organization Name',
        'instructions' => "The name of this site's organization.",
    ],

    'organization_logo' => [
        'display' => 'Organization Logo',
        'instructions' => 'Add the logo with a minimum size of 112 x 112 pixels.',
    ],

    'person_name' => [
        'display' => 'Person Name',
        'instructions' => "The name of this site's person.",
    ],

    'site_json_ld' => [
        'display' => 'JSON-LD Schema',
        'instructions' => 'Structured data that will be added to every page. This will be wrapped in the appropriate script tag.',
    ],

    'section_breadcrumbs' => [
        'display' => 'Breadcrumbs',
        'instructions' => "Breadcrumbs help your users understand your site by indicating each page's position in the hierarchy.",
    ],

    'use_breadcrumbs' => [
        'display' => 'Breadcrumbs',
        'instructions' => 'Add [breadcrumbs](https://developers.google.com/search/docs/data-types/breadcrumb) to your pages.',
    ],

    'section_crawling' => [
        'display' => 'Crawling',
        'instructions' => 'Configure the crawling settings of your site. These settings will take precedence over their counterparts on entries and terms.',
    ],

    'noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Prevent your site from being indexed by search engines.',
    ],

    'nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Prevent site crawlers from following any links on your site.',
    ],

    'section_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Configure the sitemap settings of your site.',
    ],

    'section_verification' => [
        'display' => 'Site verification',
        'instructions' => 'Verify your ownership of this site.',
    ],

    'google_site_verification_code' => [
        'display' => 'Google Verification Code',
        'instructions' => 'Add your Google verification code. You can get it in [Google Search Console](https://search.google.com/search-console).',
    ],

    'bing_site_verification_code' => [
        'display' => 'Bing Verification Code',
        'instructions' => 'Add your Bing verification code. You can get it in [Bing Webmaster Tools](https://www.bing.com/toolbox/webmaster).',
    ],

    'section_social_images_generator' => [
        'display' => 'Social Images Generator',
        'instructions' => 'Configurate the settings of the social images generator.',
    ],

    'section_social_media' => [
        'display' => 'Social Media',
        'instructions' => 'Configure the site-wide social media settings.',
    ],

    'og_image' => [
        'display' => 'Social Image',
        'instructions' => 'This image will be used as a fallback if none was set on the content. The recommended size is 1200x630px. The image is automatically resized as needed.',
    ],

    'twitter_card' => [
        'display' => 'X (Twitter) Card',
        'instructions' => 'Choose the card size to use when sharing content from this collection.',
        'summary' => 'Small',
        'summary_large_image' => 'Large',
    ],

    'twitter_handle' => [
        'display' => 'X (Twitter) Username',
        'instructions' => 'Add your X (Twitter) username.',
    ],

];
