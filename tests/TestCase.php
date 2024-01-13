<?php

namespace Aerni\AdvancedSeo\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Extend\Manifest;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Statamic;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Stache::store('seo')->directory(__DIR__.'/__fixtures__/content/seo');

        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[PreventSavingStacheItemsToDisk::class])) {
            $this->preventSavingStacheItemsToDisk();
        }
    }

    public function tearDown(): void
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[PreventSavingStacheItemsToDisk::class])) {
            $this->deleteFakeStacheDirectory();
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Aerni\AdvancedSeo\ServiceProvider::class,
            \Statamic\Providers\StatamicServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Manifest::class)->manifest = [
            'aerni/advanced-seo' => [
                'id' => 'aerni/advanced-seo',
                'namespace' => 'Aerni\\AdvancedSeo',
            ],
        ];
    }

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'assets',
            'cp',
            'forms',
            'git',
            'routes',
            'sites',
            'stache',
            'static_caching',
            'system',
            'users',
        ];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require (__DIR__."/../vendor/statamic/cms/config/{$config}.php"));
        }

        // Creat two site for multi site testing
        $app['config']->set('statamic.sites.sites', [
            'default' => ['name' => 'English', 'locale' => 'en_US', 'url' => '/'],
            'german' => ['name' => 'Deutsch', 'locale' => 'de_DE', 'url' => '/de/'],
        ]);

        // Setting the user repository to the default flat file system
        $app['config']->set('statamic.users.repository', 'file');

        // Set the content paths for our stache stores
        $app['config']->set('statamic.stache.stores.taxonomies.directory', __DIR__.'/__fixtures__/content/taxonomies');
        $app['config']->set('statamic.stache.stores.terms.directory', __DIR__.'/__fixtures__/content/taxonomies');
        $app['config']->set('statamic.stache.stores.collections.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.entries.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.navigation.directory', __DIR__.'/__fixtures__/content/navigation');
        $app['config']->set('statamic.stache.stores.collection-trees.directory', __DIR__.'/__fixtures__/content/trees/collections');
        $app['config']->set('statamic.stache.stores.nav-trees.directory', __DIR__.'/__fixtures__/content/trees/navigation');
        $app['config']->set('statamic.stache.stores.globals.directory', __DIR__.'/__fixtures__/content/globals');
        $app['config']->set('statamic.stache.stores.asset-containers.directory', __DIR__.'/__fixtures__/content/assets');
        $app['config']->set('statamic.stache.stores.users.directory', __DIR__.'/__fixtures__/users');

        // Assume the pro edition for our tests
        $app['config']->set('statamic.editions.pro', true);

        // Enable the git integration
        $app['config']->set('statamic.git.enabled', true);

        // Define the addon config for our tests
        $app['config']->set('advanced-seo', require (__DIR__.'/../config/advanced-seo.php'));
    }
}
