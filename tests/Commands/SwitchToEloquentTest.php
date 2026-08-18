<?php

use Aerni\AdvancedSeo\Eloquent\RedirectErrorModel;
use Aerni\AdvancedSeo\Eloquent\RedirectHitModel;
use Aerni\AdvancedSeo\Eloquent\RedirectModel;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfigModel;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalizationModel;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(FakesComposerLock::class, PreventsSavingStacheItemsToDisk::class, RefreshDatabase::class);

beforeEach(function () {
    Composer::shouldReceive('isInstalled')->andReturnTrue();

    File::ensureDirectoryExists(config_path());
    File::copy(__DIR__.'/../../config/advanced-seo.php', config_path('advanced-seo.php'));

    Collection::make('pages')->saveQuietly();
});

afterEach(function () {
    File::delete(config_path('advanced-seo.php'));
    File::delete([
        database_path('migrations/2026_01_13_100000_create_seo_set_configs_table.php'),
        database_path('migrations/2026_01_13_100001_create_seo_set_localizations_table.php'),
        database_path('migrations/2026_01_13_100002_migrate_seo_defaults_to_new_tables.php'),
        database_path('migrations/2026_06_23_100000_create_redirects_table.php'),
        database_path('migrations/2026_07_02_100000_create_redirect_hits_table.php'),
        database_path('migrations/2026_07_02_100001_create_redirect_errors_table.php'),
    ]);
});

it('imports all stores into the database', function () {
    SeoConfig::make()->seoSet('collections::pages')->save();
    SeoLocalization::make()->seoSet('collections::pages')->locale('default')->set('seo_title', 'Imported title')->save();

    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->save();
    Redirect::hits()->make()->redirect('abc')->count(7)->lastHitAt(1751450400)->save();
    Redirect::errors()->make()->id('err1')->url('/missing')->site('default')->count(3)
        ->firstSeenAt(1751450400)
        ->lastSeenAt(1751450500)
        ->save();

    $this->artisan('seo:switch-to-eloquent')
        ->expectsConfirmation('Do you want to import existing data into the database?', 'yes')
        ->assertSuccessful();

    $config = SeoSetConfigModel::query()->where('type', 'collections')->where('handle', 'pages')->first();
    $localization = SeoSetLocalizationModel::query()
        ->where('type', 'collections')
        ->where('handle', 'pages')
        ->where('locale', 'default')
        ->first();
    $redirect = RedirectModel::query()->find('abc');
    $hit = RedirectHitModel::query()->find('abc');
    $error = RedirectErrorModel::query()->find('err1');

    expect($config)->not->toBeNull()
        ->and($localization)->not->toBeNull()
        ->and($localization->data->get('seo_title'))->toBe('Imported title')
        ->and($redirect)->not->toBeNull()
        ->and($redirect->source)->toBe('/old')
        ->and($redirect->destination)->toBe('/new')
        ->and($hit)->not->toBeNull()
        ->and($hit->count)->toBe(7)
        ->and($hit->last_hit_at)->toBe(1751450400)
        ->and($error)->not->toBeNull()
        ->and($error->url)->toBe('/missing')
        ->and($error->count)->toBe(3)
        ->and($error->first_seen_at)->toBe(1751450400)
        ->and($error->last_seen_at)->toBe(1751450500);
});

it('imports successfully when stores are empty', function () {
    $this->artisan('seo:switch-to-eloquent')
        ->expectsConfirmation('Do you want to import existing data into the database?', 'yes')
        ->assertSuccessful();

    expect(RedirectModel::query()->count())->toBe(0)
        ->and(RedirectHitModel::query()->count())->toBe(0)
        ->and(RedirectErrorModel::query()->count())->toBe(0);
});
