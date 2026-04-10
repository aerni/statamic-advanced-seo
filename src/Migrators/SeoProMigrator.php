<?php

namespace Aerni\AdvancedSeo\Migrators;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoProMigrator extends BaseMigrator
{
    protected function fields(): Collection
    {
        return collect([
            'seo' => collect([
                'title' => 'seo_title',
                'description' => 'seo_description',
                'canonical_url' => 'seo_canonical_custom',
                'priority' => 'seo_sitemap_priority',
                'change_frequency' => 'seo_sitemap_change_frequency',
                'json_ld_schema' => 'seo_json_ld',
                'og_title' => 'seo_og_title',
                'enabled' => null,
                'image' => null,
                'robots' => null,
                'robots_indexing' => null,
                'robots_following' => null,
                'robots_noarchive' => null,
                'robots_noimageindex' => null,
                'robots_nosnippet' => null,
                'site_name' => null,
                'site_name_position' => null,
                'site_name_separator' => null,
                'twitter_handle' => null,
                'twitter_title' => null,
                'twitter_description' => null,
                'sitemap' => null,
            ]),
        ]);
    }

    protected function update(Collection $data): Collection
    {
        $nonSeoFields = $data->diffKeys($this->fields());
        $seoFieldsToMigrate = collect($data->get('seo'))->intersectByKeys($this->fields()->get('seo')->filter());

        $migratedSeoFields = $seoFieldsToMigrate->mapWithKeys(fn ($value, $key) => [$this->fields()->get('seo')->get($key) => $value])->filter();

        // We want to remove any field that was disabled by the user.
        $oldData = collect($data->get('seo'))->filter();

        return $nonSeoFields
            ->merge($migratedSeoFields)
            ->pipe(fn ($data) => $this->transform($data, $oldData));
    }

    protected function transform(Collection $data, ?Collection $oldData = null): Collection
    {
        $robots = Arr::get($oldData, 'robots', []);
        $image = Arr::get($oldData, 'image');

        $transformed = collect([
            'seo_noindex' => in_array('noindex', $robots) || Arr::get($oldData, 'robots_indexing') === 'noindex' ? true : null,
            'seo_nofollow' => in_array('nofollow', $robots) || Arr::get($oldData, 'robots_following') === 'nofollow' ? true : null,
            'seo_canonical_type' => $data->has('seo_canonical_custom') ? 'custom' : null,
            'seo_og_image' => $image ?? null,
        ])->filter();

        return $data
            ->merge($transformed)
            ->map(
                fn ($value) => is_string($value) && Str::contains($value, '@seo:')
                ? preg_replace('/@seo:([A-Za-z\d_-]+)/', '{{ $1 }}', $value)
                : $value
            );
    }
}
