<?php

namespace Aerni\AdvancedSeo\Tests;

use Aerni\AdvancedSeo\ServiceProvider;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesGraphQL;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesSitemap;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\GraphQL\TypeRegistrar;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('statamic.system.multisite', true);

        $app['config']->set('advanced-seo', require (__DIR__.'/../config/advanced-seo.php'));

        $app['config']->set('advanced-seo.directory', __DIR__.'/__fixtures__/content/seo');

        if ($this->usesEloquentDriver()) {
            $app['config']->set('advanced-seo.driver', 'eloquent');
        }

        if ($this->usesGraphQL()) {
            $app['config']->set('statamic.graphql.enabled', true);
            $app['config']->set('advanced-seo.graphql', true);
        }

        if ($this->usesSitemap()) {
            $app['config']->set('advanced-seo.sitemap.enabled', true);
            $app['config']->set('advanced-seo.crawling.environments', ['testing']);
        }
    }

    protected function setUp(): void
    {
        /* Need to copy the correct composer.lock file to the correct location so that Composer::isInstalled() won't fail in the service provider. */
        $this->usesEloquentDriver()
            ? copy(__DIR__.'/__fixtures__/composer.eloquent.lock', __DIR__.'/../vendor/orchestra/testbench-core/laravel/composer.lock')
            : copy(__DIR__.'/__fixtures__/composer.empty.lock', __DIR__.'/../vendor/orchestra/testbench-core/laravel/composer.lock');

        parent::setUp();

        if ($this->usesGraphQL()) {
            app(TypeRegistrar::class)->register();
        }
    }

    protected function tearDown(): void
    {
        /* Delete the previously copied composer.lock file. */
        unlink(__DIR__.'/../vendor/orchestra/testbench-core/laravel/composer.lock');

        parent::tearDown();
    }

    protected function usesEloquentDriver(): bool
    {
        return isset(array_flip(class_uses_recursive(static::class))[UseEloquentDriver::class]);
    }

    protected function usesGraphQL(): bool
    {
        return isset(array_flip(class_uses_recursive(static::class))[EnablesGraphQL::class]);
    }

    protected function usesSitemap(): bool
    {
        return isset(array_flip(class_uses_recursive(static::class))[EnablesSitemap::class]);
    }
}
