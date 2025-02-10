<?php

namespace Aerni\AdvancedSeo\Tests;

use Aerni\AdvancedSeo\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('advanced-seo', require (__DIR__.'/../config/advanced-seo.php'));

        $app['config']->set('advanced-seo.directory', __DIR__.'/__fixtures__/content/seo');
    }

    protected function setUp(): void
    {
        /* Need to copy this file to the correct location so that Composer::isInstalled() won't fail in the service provider. */
        copy(__DIR__.'/__fixtures__/composer.empty.lock', __DIR__.'/../vendor/orchestra/testbench-core/laravel/composer.lock');

        parent::setUp();
    }

    protected function teardown(): void
    {
        /* Delete the previously copied composer.lock file. */
        unlink(__DIR__.'/../vendor/orchestra/testbench-core/laravel/composer.lock');

        parent::teardown();
    }
}
