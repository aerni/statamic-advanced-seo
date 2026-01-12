<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

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
        $localizations = collect(json_decode($row->data, true));

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
