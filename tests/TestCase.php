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
}
