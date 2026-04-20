<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;
use Statamic\Facades\Site;

return new class extends Migration
{
    public function up(): void
    {
        $oldTable = $this->prefix('advanced_seo_defaults');

        if (! Schema::hasTable($oldTable)) {
            return;
        }

        DB::transaction(fn () => DB::table($oldTable)
            ->orderBy('id')
            ->chunk(100, fn ($rows) => $rows->each($this->migrateRow(...))));

        Schema::drop($oldTable);
    }

    protected function migrateRow(object $row): void
    {
        $data = json_decode($row->data, true);

        $this->isSingleSiteData($data)
            ? $this->migrateSingleSiteRow($row, $data)
            : $this->migrateMultiSiteRow($row, $data);
    }

    /**
     * Detect if the data is in single-site format.
     *
     * Single-site format has SEO field names as keys:
     *   {"seo_title": "Title", "seo_description": "Description"}
     *
     * Multi-site format has site handles as keys:
     *   {"default": {"seo_title": "Title"}, "german": {"seo_title": "..."}}
     *
     * We detect by checking if any data key is NOT a known site handle.
     */
    protected function isSingleSiteData(array $data): bool
    {
        return collect($data)
            ->keys()
            ->diff(Site::all()->keys())
            ->isNotEmpty();
    }

    /**
     * Migrate a single-site data row.
     *
     * Creates one localization for the default site with all the data.
     */
    protected function migrateSingleSiteRow(object $row, array $data): void
    {
        DB::table($this->prefix('seo_set_localizations'))
            ->insert([
                'type' => $row->type,
                'handle' => $row->handle,
                'locale' => Site::default()->handle(),
                'data' => json_encode($data),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

        DB::table($this->prefix('seo_set_configs'))
            ->insert([
                'type' => $row->type,
                'handle' => $row->handle,
                'data' => json_encode([]),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
    }

    /**
     * Migrate a multi-site data row.
     *
     * Creates one localization per site and extracts origins to config.
     */
    protected function migrateMultiSiteRow(object $row, array $data): void
    {
        $localizations = collect($data);

        foreach ($localizations as $locale => $data) {
            DB::table($this->prefix('seo_set_localizations'))->insert([
                'type' => $row->type,
                'handle' => $row->handle,
                'locale' => $locale,
                'data' => json_encode(Arr::except($data, 'origin')),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $origins = $localizations
            ->map(fn ($data) => $data['origin'] ?? null)
            ->filter()
            ->all();

        DB::table($this->prefix('seo_set_configs'))->insert([
            'type' => $row->type,
            'handle' => $row->handle,
            'data' => json_encode(array_filter(['origins' => $origins])),
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ]);
    }
};
