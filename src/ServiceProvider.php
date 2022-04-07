<?php

namespace Aerni\AdvancedSeo;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Stache\SeoStore;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Git;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $actions = [
        Actions\GenerateSocialImages::class,
    ];

    protected $commands = [
        Commands\MakeTheme::class,
        Commands\Migrate::class,
    ];

    protected $fieldtypes = [
        Fieldtypes\SocialImageFieldtype::class,
        Fieldtypes\SourceFieldtype::class,
    ];

    // protected $listen = [
    //     \Aerni\AdvancedSeo\Events\SeoDefaultSetSaved::class => [
    //         \Aerni\AdvancedSeo\Listeners\GenerateFavicons::class,
    //     ],
    // ];

    protected $subscribe = [
        \Aerni\AdvancedSeo\Subscribers\ContentDefaultsSubscriber::class,
        \Aerni\AdvancedSeo\Subscribers\OnPageSeoBlueprintSubscriber::class,
        \Aerni\AdvancedSeo\Subscribers\SitemapCacheSubscriber::class,
        \Aerni\AdvancedSeo\Subscribers\SocialImagesGeneratorSubscriber::class,
    ];

    protected $tags = [
        Tags\AdvancedSeoTags::class,
    ];

    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js',
    ];

    protected $stylesheets = [
        __DIR__.'/../resources/dist/css/cp.css',
    ];

    protected $policies = [
        \Aerni\AdvancedSeo\Data\SeoVariables::class => \Aerni\AdvancedSeo\Policies\SeoVariablesPolicy::class,
    ];

    public $singletons = [
        \Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository::class => \Aerni\AdvancedSeo\Stache\SeoDefaultsRepository::class,
    ];

    public function bootAddon(): void
    {
        $this
            ->bootAddonStores()
            ->bootAddonNav()
            ->bootAddonPermissions()
            ->bootGit()
            ->autoPublishConfig()
            ->publishSocialImagesViews();
    }

    protected function bootAddonStores(): self
    {
        $seoStore = app(SeoStore::class)->directory(base_path('content/seo'));

        app(Stache::class)->registerStore($seoStore);

        return $this;
    }

    protected function bootAddonNav(): self
    {
        Nav::extend(function ($nav) {
            Defaults::enabled()->groupBy('type')->each(function ($items, $type) use ($nav) {
                $nav->create(ucfirst($type))
                    ->section('SEO')
                    ->can('index', [SeoVariables::class, $type])
                    ->route("advanced-seo.{$type}.index")
                    ->active("advanced-seo/{$type}")
                    ->icon($items->first()['type_icon'])
                    ->children(
                        $items->map(function ($item) use ($nav, $type) {
                            return $nav->item($item['title'])
                                ->can('view', [SeoVariables::class, $item['handle']])
                                ->route("advanced-seo.{$item['type']}.edit", $item['handle'])
                                ->active("advanced-seo/{$type}/{$item['handle']}");
                        })->toArray()
                    );
            });
        });

        return $this;
    }

    protected function bootAddonPermissions(): self
    {
        Permission::group('advanced-seo', 'Advanced SEO', function () {
            Defaults::enabled()->groupBy('type')->each(function ($items, $group) {
                Permission::register("view seo {$group} defaults", function ($permission) use ($group, $items) {
                    $permission
                        ->label('View ' . ucfirst($group))
                        ->children([
                            Permission::make('view seo {group} defaults')
                                ->label('View :group')
                                ->replacements('group', function () use ($items) {
                                    return $items->map(function ($item) {
                                        return [
                                            'value' => $item['handle'],
                                            'label' => $item['title'],
                                        ];
                                    });
                                })
                                ->children([
                                    Permission::make('edit seo {group} defaults')
                                        ->label('Edit :group'),
                                ]),
                        ]);
                });
            });
        });

        return $this;
    }

    protected function bootGit(): self
    {
        if (config('statamic.git.enabled')) {
            Git::listen(\Aerni\AdvancedSeo\Events\SeoDefaultSetSaved::class);
        }

        return $this;
    }

    protected function publishSocialImagesViews(): self
    {
        $this->publishes([
            __DIR__.'/../resources/views/social_images' => resource_path('views/vendor/advanced-seo/social_images'),
        ], 'advanced-seo-views');

        return $this;
    }

    protected function autoPublishConfig(): self
    {
        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', [
                '--tag' => 'advanced-seo-config',
            ]);
        });

        return $this;
    }
}
