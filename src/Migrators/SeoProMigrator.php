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
                'image' => null,
                'robots' => null,
                'site_name' => null,
                'site_name_position' => null,
                'site_name_separator' => null,
                'twitter_handle' => null,
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
            ->pipe(fn ($data) => $this->transform($data, $oldData)
            ->pipe(fn ($data) => $this->parse($data))
            ->pipe(fn ($data) => $this->addMissingFields($data)));
    }

    protected function transform(Collection $data, ?Collection $oldData = null): Collection
    {
        $robots = Arr::get($oldData, 'robots', []);
        $image = Arr::get($oldData, 'image');

        $transformed = collect([
            'seo_noindex' => in_array('noindex', $robots) ? true : null,
            'seo_nofollow' => in_array('nofollow', $robots) ? true : null,
            'seo_canonical_type' => $data->has('seo_canonical_custom') ? 'custom' : null,
            'seo_og_image' => $image ?? null,
            'seo_twitter_summary_image' => $image ?? null,
            'seo_twitter_summary_large_image' => $image ?? null,
        ])->filter();

        return $data->merge($transformed);
    }

    protected function parse(Collection $data): Collection
    {
        $parsed = $data
            ->filter(fn ($value) => Str::contains($value, '@seo:'))
            ->map(fn ($value) => $data->get(Str::remove('@seo:', $value)));

        return $data->merge($parsed)->filter();
    }
}
