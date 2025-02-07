<?php

namespace Aerni\AdvancedSeo\Tests;

use ReflectionClass;
use Statamic\Testing\AddonTestCase;
use Aerni\AdvancedSeo\ServiceProvider;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

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
}
