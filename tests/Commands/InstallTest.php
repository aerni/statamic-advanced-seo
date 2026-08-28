<?php

use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

uses(FakesComposerLock::class);

beforeEach(function () {
    $this->uninstallPackages();

    File::ensureDirectoryExists(config_path());
    File::copy(__DIR__.'/../../config/advanced-seo.php', config_path('advanced-seo.php'));
});

afterEach(function () {
    File::delete(config_path('advanced-seo.php'));
});

it('enables redirects and installs simple-excel when selected', function () {
    Composer::shouldReceive('isInstalled')->andReturnFalse();
    Composer::shouldReceive('withoutQueue->throwOnFailure->require')->once()->with('spatie/simple-excel');

    $this->artisan('seo:install')
        ->expectsQuestion('Select the Pro features you would like to set up.', ['redirects'])
        ->expectsQuestion('Select an addon to migrate from.', 'none')
        ->assertExitCode(0);

    expect(File::get(config_path('advanced-seo.php')))
        ->toMatch('/Disable to turn off redirect handling.*?\n.*?\n.*?\n.*?\n.*?\n.*?\'enabled\' => true/s');
});

it('installs the eloquent driver without switching when migration is declined', function () {
    Composer::shouldReceive('isInstalled')->andReturnTrue();
    Process::fake();

    $this->artisan('seo:install')
        ->expectsQuestion('Select the Pro features you would like to set up.', ['eloquent'])
        ->expectsConfirmation('Would you like to switch to Eloquent and migrate your existing SEO data now?', 'no')
        ->expectsQuestion('Select an addon to migrate from.', 'none')
        ->assertExitCode(0);

    Process::assertNothingRan();

    expect(File::get(config_path('advanced-seo.php')))
        ->toContain("'driver' => 'file'");
});

it('switches to eloquent and migrates when confirmed', function () {
    Composer::shouldReceive('isInstalled')->andReturnTrue();
    Process::fake();

    $this->artisan('seo:install')
        ->expectsQuestion('Select the Pro features you would like to set up.', ['eloquent'])
        ->expectsConfirmation('Would you like to switch to Eloquent and migrate your existing SEO data now?', 'yes')
        ->expectsQuestion('Select an addon to migrate from.', 'none')
        ->assertExitCode(0);

    Process::assertRan(fn ($process) => $process->command === [
        PHP_BINARY,
        defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan',
        'seo:switch-to-eloquent',
        '--no-interaction',
    ]);
});
