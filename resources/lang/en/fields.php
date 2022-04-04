<?php

use Aerni\AdvancedSeo\Facades\SocialImage;

return [

    'seo_section_title_description' => [
        'instructions' => 'Configure the title and description of this :type.',
        'default_instructions' => 'Configure the default title and description of your :type.',
    ],

    'seo_title' => [
        'instructions' => 'Set the meta title of this :type.',
        'default_instructions' => 'Set the default meta title of your :type.',
    ],

    'seo_description' => [
        'instructions' => 'Set the meta description of this :type.',
        'default_instructions' => 'Set the default meta description of your :type.',
    ],

    'seo_section_social_images_generator' => [
        'instructions' => 'Configure the generator settings of this :type.',
        'default_instructions' => 'Configure the default generator settings of your :type.',
    ],

    'seo_generate_social_images' => [
        'instructions' => 'Activate to generate the Open Graph and Twitter images of this :type.',
        'default_instructions' => 'Activate to generate the :type\' Open Graph and Twitter images by default.',
    ],

    'seo_og_image_preview' => [
        'instructions' => 'Reload the page after save to update the preview.',
    ],

    'seo_twitter_image_preview' => [
        'instructions' => 'Reload the page after save to update the preview.',
    ],

    'seo_section_og' => [
        'instructions' => 'Configure the Open Graph settings of this :type.',
        'default_instructions' => 'Configure the default Open Graph settings of your :type.',
    ],

    'seo_og_title' => [
        'instructions' => 'Set the Open Graph title of this :type.',
        'default_instructions' => 'Set the default Open Graph title of your :type.',
    ],

    'seo_og_description' => [
        'instructions' => 'Set the Open Graph description of this :type.',
        'default_instructions' => 'Set the default Open Graph description of your :type.',
    ],

    'seo_og_image' => [
        'instructions' => 'Add an Open Graph image for this :type. It will be cropped to ' . SocialImage::sizeString('og') . '.',
        'default_instructions' => 'Add a default Open Graph image for your :type. It will be cropped to ' . SocialImage::sizeString('og') . '.',
    ],

    'seo_section_twitter' => [
        'instructions' => 'Configure the Twitter settings of this :type.',
        'default_instructions' => 'Configure the default Twitter settings of your :type.',
    ],

    'seo_twitter_card' => [
        'instructions' => 'Choose the type of card to use when sharing this :type.',
        'default_instructions' => 'Choose the default type of card to use when sharing your :type.',
    ],

    'seo_twitter_title' => [
        'instructions' => 'Set the Twitter title of this :type.',
        'default_instructions' => 'Set the default Twitter title of your :type.',
    ],

    'seo_twitter_description' => [
        'instructions' => 'Set the Twitter description of this :type.',
        'default_instructions' => 'Set the default Twitter description of your :type.',
    ],

    'seo_twitter_summary_image' => [
        'instructions' => 'Add a Twitter image for this :type. It will be cropped to ' . SocialImage::sizeString('twitter.summary') . '.',
        'default_instructions' => 'Add a default Twitter image for your :type. It will be cropped to ' . SocialImage::sizeString('twitter.summary') . '.',
    ],

    'seo_twitter_summary_large_image' => [
        'instructions' => 'Add a Twitter image for this :type. It will be cropped to ' . SocialImage::sizeString('twitter.summary_large_image') . '.',
        'default_instructions' => 'Add a default Twitter image for your :type. It will be cropped to ' . SocialImage::sizeString('twitter.summary_large_image') . '.',
    ],

    'seo_section_canonical_url' => [
        'instructions' => 'Configure the canonical URL settings of this :type.',
        'default_instructions' => 'Configure the default canonical URL settings of your :type.',
    ],

    'seo_canonical_type' => [
        'instructions' => 'Where should the canonical URL of this :type point to.',
        'default_instructions' => 'Where should the default canonical URL of your :type point to.',
    ],

    'seo_canonical_entry' => [
        'instructions' => 'Choose the entry with the original content.',
        'default_instructions' => 'Choose the entry with the original content.',
    ],

    'seo_canonical_custom' => [
        'instructions' => 'A fully qualified URL starting with https://.',
        'default_instructions' => 'A fully qualified URL starting with https://.',
    ],

    'seo_section_indexing' => [
        'instructions' => 'Configure the indexing settings of this :type.',
        'default_instructions' => 'Configure the default indexing settings of your :type.',
    ],

    'seo_noindex' => [
        'instructions' => 'Prevent this :type from being indexed by search engines.',
        'default_instructions' => 'Prevent your :type from being indexed by search engines.',
    ],

    'seo_nofollow' => [
        'instructions' => 'Prevent site crawlers from following links on this :type\'s page.',
        'default_instructions' => 'Prevent site crawlers from following links on your :type\' pages.',
    ],

    'seo_section_sitemap' => [
        'instructions' => 'Configure the sitemap settings of this :type.',
        'default_instructions' => 'Configure the default sitemap settings of your :type.',
    ],

    'seo_sitemap_priority' => [
        'instructions' => 'Choose the sitemap priority of this :type. 1.0 is the most important.',
        'default_instructions' => 'Choose the default sitemap priority of your :type. 1.0 is the most important.',
    ],

    'seo_sitemap_change_frequency' => [
        'instructions' => 'Choose the frequency in which search engines should crawl this :type.',
        'default_instructions' => 'Choose the default frequency in which search engines should crawl your :type.',
    ],

    'seo_section_json_ld' => [
        'instructions' => 'Add custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for this :type.',
        'default_instructions' => 'Add default custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for your :type.',
    ],

    'seo_json_ld' => [
        'instructions' => 'The structured data of this :type. This will be wrapped in the appropriate script tag.',
        'default_instructions' => 'The default structured data of your :type. This will be wrapped in the appropriate script tag.',
    ],

];
