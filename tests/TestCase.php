<?php

namespace Aerni\AdvancedSeo\Tests;

use Aerni\AdvancedSeo\ServiceProvider;
use ReflectionClass;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $reflector = new ReflectionClass($this->addonServiceProvider);
        $directory = dirname($reflector->getFileName());

        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('advanced-seo', require (__DIR__.'/../config/advanced-seo.php'));

        $app['config']->set('advanced-seo.directory', $directory.'/../tests/__fixtures__/content/seo');

    }
}
