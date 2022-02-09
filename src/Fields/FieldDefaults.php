<?php

namespace Aerni\AdvancedSeo\Fields;

class FieldDefaults
{
    protected static array $defaults = [
        // Those are used on the content defaults and on-page-seo.
        'seo_generate_social_images' => false,
        'seo_twitter_card' => 'summary',
        'seo_canonical_type' => 'current',
        'seo_noindex' => false,
        'seo_nofollow' => false,
        'seo_sitemap_priority' => '0.5',
        'seo_sitemap_change_frequency' => 'daily',

        // Those are used in the site defaults.
        'title_separator' => '|',
        'title_position' => 'before',
        'site_json_ld_type' => 'none',
    ];

    public static function get(string $key): mixed
    {
        if (array_key_exists($key, self::$defaults)) {
            return self::$defaults[$key];
        }

        throw new \Exception("No default value for field [$key] defined.");
    }
}
