<?php

use Aerni\AdvancedSeo\Eloquent\SeoSetConfigModel;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalizationModel;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\Stache;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(UseEloquentDriver::class, PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    File::ensureDirectoryExists(config_path());
    File::copy(__DIR__.'/../../config/advanced-seo.php', config_path('advanced-seo.php'));
    File::put(
        config_path('advanced-seo.php'),
        preg_replace("/('driver'\s*=>\s*)'file'/", "\${1}'eloquent'", File::get(config_path('advanced-seo.php')), 1)
    );

    Collection::make('pages')->saveQuietly();
});

afterEach(function () {
    File::delete(config_path('advanced-seo.php'));
});

it('exports all stores to files', function () {
    SeoConfig::make()->seoSet('collections::pages')->save();
    SeoLocalization::make()->seoSet('collections::pages')->locale('default')->set('seo_title', 'Exported title')->save();

    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->save();
    Redirect::hits()->make()->redirect('abc')->count(7)->lastHitAt(1751450400)->save();
    Redirect::errors()->make()->id('err1')->url('/missing')->site('default')->count(3)
        ->firstSeenAt(1751450400)
        ->lastSeenAt(1751450500)
        ->save();

    $this->artisan('seo:switch-to-file')
        ->expectsConfirmation('Do you want to export existing data to flat-files?', 'yes')
        ->assertSuccessful();

    $config = Stache::store('seo-set-configs')->getItem('collections::pages');
    $localization = Stache::store('seo-set-localizations')->getItem('collections::pages::default');
    $redirect = Stache::store('redirects')->getItem('abc');
    $hit = Stache::store('redirect-hits')->getItem('abc');
    $error = Stache::store('redirect-errors')->getItem('err1');

    expect($config)->not->toBeNull()
        ->and($localization)->not->toBeNull()
        ->and($localization->get('seo_title'))->toBe('Exported title')
        ->and($redirect)->not->toBeNull()
        ->and($redirect->source())->toBe('/old')
        ->and($redirect->destination())->toBe('/new')
        ->and($hit)->not->toBeNull()
        ->and($hit->count())->toBe(7)
        ->and($hit->lastHitAt())->toBe(1751450400)
        ->and($error)->not->toBeNull()
        ->and($error->url())->toBe('/missing')
        ->and($error->count())->toBe(3)
        ->and($error->firstSeenAt())->toBe(1751450400)
        ->and($error->lastSeenAt())->toBe(1751450500);
});

it('exports registered sets and skips configs whose seo set no longer exists', function () {
    SeoConfig::make()->seoSet('collections::pages')->save();

    SeoSetConfigModel::query()->create([
        'type' => 'site',
        'handle' => 'general',
        'data' => [],
    ]);
    SeoSetConfigModel::query()->create([
        'type' => 'collections',
        'handle' => 'deleted',
        'data' => ['enabled' => true],
    ]);
    SeoSetLocalizationModel::query()->create([
        'type' => 'site',
        'handle' => 'general',
        'locale' => 'default',
        'data' => ['seo_title' => 'Legacy'],
    ]);

    $this->artisan('seo:switch-to-file')
        ->expectsConfirmation('Do you want to export existing data to flat-files?', 'yes')
        ->assertSuccessful();

    expect(Stache::store('seo-set-configs')->getItem('collections::pages'))->not->toBeNull()
        ->and(Stache::store('seo-set-configs')->getItem('site::general'))->toBeNull()
        ->and(Stache::store('seo-set-configs')->getItem('collections::deleted'))->toBeNull()
        ->and(Stache::store('seo-set-localizations')->getItem('site::general::default'))->toBeNull();
});

it('exports successfully when stores are empty', function () {
    $this->artisan('seo:switch-to-file')
        ->expectsConfirmation('Do you want to export existing data to flat-files?', 'yes')
        ->assertSuccessful();

    expect(Stache::store('redirects')->paths())->toHaveCount(0)
        ->and(Stache::store('redirect-hits')->paths())->toHaveCount(0)
        ->and(Stache::store('redirect-errors')->paths())->toHaveCount(0);
});
