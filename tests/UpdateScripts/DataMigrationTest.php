<?php

use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(UseEloquentDriver::class);

function runDataMigration(): void
{
    $migration = require __DIR__.'/../../database/migrations/2026_01_13_100002_migrate_seo_defaults_to_new_tables.php';
    $migration->up();
}

it('skips migration when old table does not exist', function () {
    runDataMigration();

    expect(DB::table('seo_set_configs')->count())->toBe(0);
    expect(DB::table('seo_set_localizations')->count())->toBe(0);
});

it('migrates data from old table to new tables', function () {
    // Create the old table
    Schema::create('advanced_seo_defaults', function ($table) {
        $table->id();
        $table->string('type');
        $table->string('handle');
        $table->json('data');
        $table->timestamps();

        $table->unique(['type', 'handle']);
    });

    // Add data to the old table
    DB::table('advanced_seo_defaults')->insert([
        'type' => 'collections',
        'handle' => 'pages',
        'data' => json_encode([
            'default' => ['seo_title' => 'Default Title'],
            'german' => ['seo_title' => 'German Title', 'origin' => 'default'],
        ]),
        'created_at' => '2024-01-15 10:30:00',
        'updated_at' => '2024-06-20 14:45:00',
    ]);

    runDataMigration();

    // Old table is dropped
    expect(Schema::hasTable('advanced_seo_defaults'))->toBeFalse();

    // Config record created with origins
    $config = DB::table('seo_set_configs')->first();
    expect($config->type)->toBe('collections');
    expect($config->handle)->toBe('pages');
    expect(json_decode($config->data, true))->toBe(['origins' => ['german' => 'default']]);
    expect($config->created_at)->toBe('2024-01-15 10:30:00');
    expect($config->updated_at)->toBe('2024-06-20 14:45:00');

    // Localization records created without origin key
    $localizations = DB::table('seo_set_localizations')->get();
    expect($localizations)->toHaveCount(2);

    $default = $localizations->firstWhere('locale', 'default');
    expect(json_decode($default->data, true))->toBe(['seo_title' => 'Default Title']);

    $german = $localizations->firstWhere('locale', 'german');
    expect(json_decode($german->data, true))->toBe(['seo_title' => 'German Title']);
});
