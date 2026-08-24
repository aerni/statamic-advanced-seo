<?php

use Aerni\AdvancedSeo\Enums\RedirectSourceType;
use Aerni\AdvancedSeo\UpdateScripts\AddRedirects;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->publishedMigrations = collect([
        '2026_01_13_100000_create_seo_set_configs_table.php',
        '2026_01_13_100001_create_seo_set_localizations_table.php',
        '2026_01_13_100002_migrate_seo_defaults_to_new_tables.php',
        '2026_06_23_100000_create_redirects_table.php',
        '2026_07_02_100000_create_redirect_hits_table.php',
        '2026_07_02_100001_create_redirect_errors_table.php',
    ])->map(fn (string $migration) => database_path("migrations/{$migration}"));

    File::ensureDirectoryExists(config_path());
    File::put(config_path('advanced-seo.php'), <<<'PHP'
<?php

return [

    'driver' => 'file',

];
PHP);
});

afterEach(function () {
    File::delete(config_path('advanced-seo.php'));
    File::delete($this->publishedMigrations->all());
});

it('adds the complete redirects configuration to the published config', function () {
    (new AddRedirects('aerni/advanced-seo'))->update();

    $redirects = File::getRequire(config_path('advanced-seo.php'))['redirects'] ?? null;

    expect($redirects)->toBe([
        'enabled' => false,
        'directory' => base_path('content/redirects'),
        'hits' => [
            'enabled' => true,
            'directory' => storage_path('statamic/advanced-seo/redirect-hits'),
        ],
        'errors' => [
            'enabled' => true,
            'directory' => storage_path('statamic/advanced-seo/redirect-errors'),
            'purge_after_days' => 30,
            'max_records' => 1000,
            'ignore' => [
                '#\\.php$#',
                '#/wp-(admin|includes|content)(/|$)#',
                '#^/\\.(env|git)#',
            ],
        ],
        'queue' => 'default',
    ]);
});

it('preserves an existing redirects configuration', function () {
    $config = <<<'PHP'
<?php

return [

    'driver' => 'file',

    'redirects' => [
        'enabled' => true,
        'queue' => 'redirects',
    ],

];
PHP;

    File::put(config_path('advanced-seo.php'), $config);

    (new AddRedirects('aerni/advanced-seo'))->update();

    expect(File::get(config_path('advanced-seo.php')))->toBe($config);
});

it('does not publish or run migrations for the file driver', function () {
    (new AddRedirects('aerni/advanced-seo'))->update();

    expect($this->publishedMigrations->every(fn (string $migration) => File::missing($migration)))->toBeTrue()
        ->and(Schema::hasTable('redirects'))->toBeFalse()
        ->and(Schema::hasTable('redirect_hits'))->toBeFalse()
        ->and(Schema::hasTable('redirect_errors'))->toBeFalse();
});

it('publishes and runs migrations for the eloquent driver', function () {
    $this->installEloquentDriver();

    config()->set('advanced-seo.driver', 'eloquent');

    (new AddRedirects('aerni/advanced-seo'))->update();

    expect($this->publishedMigrations->every(fn (string $migration) => File::exists($migration)))->toBeTrue()
        ->and(Schema::hasTable('redirects'))->toBeTrue()
        ->and(Schema::hasTable('redirect_hits'))->toBeTrue()
        ->and(Schema::hasTable('redirect_errors'))->toBeTrue()
        ->and(Schema::hasColumn('redirects', 'source_hash'))->toBeTrue()
        ->and(Schema::hasColumn('redirects', 'source_type'))->toBeTrue();
});

it('enforces unique source hashes per site in the redirects migration', function () {
    $createRedirects = require __DIR__.'/../../database/migrations/2026_06_23_100000_create_redirects_table.php';

    $createRedirects->up();

    DB::table('redirects')->insert([
        'id' => 'redirect-one',
        'source' => '/old',
        'source_hash' => hash('xxh128', '/old'),
        'source_type' => RedirectSourceType::Exact->value,
        'site' => 'default',
    ]);

    expect(fn () => DB::table('redirects')->insert([
        'id' => 'redirect-two',
        'source' => '/old',
        'source_hash' => hash('xxh128', '/old'),
        'source_type' => RedirectSourceType::Exact->value,
        'site' => 'default',
    ]))->toThrow(QueryException::class);
});

it('stores redirect sources as text', function () {
    $createRedirects = require __DIR__.'/../../database/migrations/2026_06_23_100000_create_redirects_table.php';

    $createRedirects->up();

    expect(Schema::getColumnType('redirects', 'source'))->toBe('text');
});

it('runs eloquent migrations when the redirects configuration already exists', function () {
    $this->installEloquentDriver();

    config()->set('advanced-seo.driver', 'eloquent');

    File::put(config_path('advanced-seo.php'), <<<'PHP'
<?php

return [

    'driver' => 'eloquent',

    'redirects' => [
        'enabled' => false,
    ],

];
PHP);

    (new AddRedirects('aerni/advanced-seo'))->update();

    expect(Schema::hasTable('redirects'))->toBeTrue()
        ->and(Schema::hasTable('redirect_hits'))->toBeTrue()
        ->and(Schema::hasTable('redirect_errors'))->toBeTrue();
});

it('does not overwrite previously published migrations', function () {
    $this->installEloquentDriver();

    config()->set('advanced-seo.driver', 'eloquent');

    $existingMigration = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void {}

    public function down(): void {}
};
PHP;

    File::put($this->publishedMigrations->first(), $existingMigration);

    (new AddRedirects('aerni/advanced-seo'))->update();

    expect(File::get($this->publishedMigrations->first()))->toBe($existingMigration)
        ->and(Schema::hasTable('redirects'))->toBeTrue();
});
