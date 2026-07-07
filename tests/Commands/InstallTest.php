<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(config_path());
    File::copy(__DIR__.'/../../config/advanced-seo.php', config_path('advanced-seo.php'));
});

afterEach(function () {
    File::delete(config_path('advanced-seo.php'));
});

it('enables redirects when selected in the installer', function () {
    $this->artisan('seo:install')
        ->expectsQuestion('Select the Pro features you would like to set up.', ['redirects'])
        ->expectsQuestion('Select an addon to migrate from.', 'none')
        ->assertExitCode(0);

    expect(File::get(config_path('advanced-seo.php')))
        ->toMatch('/Disable to turn off redirect handling.*?\n.*?\n.*?\n.*?\n.*?\n.*?\'enabled\' => true/s');
});
