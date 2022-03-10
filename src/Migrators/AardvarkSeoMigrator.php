<?php

namespace Aerni\AdvancedSeo\Migrators;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AardvarkSeoMigrator extends BaseMigrator
{
    protected function fields(): Collection
    {
        return collect([
            'no_index_page' => 'seo_noindex',
            'no_follow_links' => 'seo_nofollow',
            'sitemap_priority' => 'seo_sitemap_priority',
            'sitemap_changefreq' => 'seo_sitemap_change_frequency',
            'twitter_card_type_page' => 'seo_twitter_card',
            'meta_title' => 'seo_title',
            'meta_description' => 'seo_description',
            'og_title' => 'seo_og_title',
            'og_description' => 'seo_og_description',
            'og_image' => 'seo_og_image',
            'twitter_title' => 'seo_twitter_title',
            'twitter_description' => 'seo_twitter_description',
            'twitter_summary_image' => 'seo_twitter_summary_image',
            'twitter_summary_large_image' => 'seo_twitter_summary_large_image',
            'canonical_url' => 'seo_canonical_custom',
            'schema_objects' => 'seo_json_ld',
            'override_twitter_settings' => null,
            'override_twitter_card_settings' => null,
            'use_meta_keywords' => null,
            'meta_keywords' => null,
            'localized_urls' => null,
            'head_snippets' => null,
            'footer_snippets' => null,
        ]);
    }

    protected function transform(Collection $data): Collection
    {
        $transformed = collect([
            'seo_json_ld' => $data->has('seo_json_ld') ? $this->transformJsonLd($data->get('seo_json_ld')) : null,
            'seo_canonical_type' => $data->has('seo_canonical_custom') ? 'custom' : null,
        ])->filter();

        return $data->merge($transformed);
    }

    protected function transformJsonLd(string $value): string
    {
        return Str::of($value)
            ->remove("<script type=\"application/ld+json\">")
            ->remove("</script>")
            ->replaceFirst("\n", null);
    }
}
