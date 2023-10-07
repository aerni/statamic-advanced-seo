<?php

namespace Aerni\AdvancedSeo;

use Aerni\AdvancedSeo\Actions\ShouldProcessViewCascade;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\GraphQL\Fields\SeoField;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoDefaultsQuery;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoMetaQuery;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoSitemapsQuery;
use Aerni\AdvancedSeo\GraphQL\Types\AnalyticsDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\ComputedMetaDataType;
use Aerni\AdvancedSeo\GraphQL\Types\ContentDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\FaviconsDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\GeneralDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\HreflangType;
use Aerni\AdvancedSeo\GraphQL\Types\IndexingDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\RawMetaDataType;
use Aerni\AdvancedSeo\GraphQL\Types\RenderedViewsType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapsType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;
use Aerni\AdvancedSeo\GraphQL\Types\SiteDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapAlternatesType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialImagePresetType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialMediaDefaultsType;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Stache\SeoStore;
use Aerni\AdvancedSeo\View\ViewCascade;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Git;
use Statamic\Facades\GraphQL;
use Statamic\Facades\Permission;
use Statamic\GraphQL\Types\EntryInterface;
use Statamic\GraphQL\Types\TermInterface;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;
use Statamic\Statamic;
use Statamic\Tags\Context;

class ServiceProvider extends AddonServiceProvider
{
    protected $actions = [
        Actions\Statamic\GenerateSocialImages::class,
    ];

    protected $commands = [
        Commands\GenerateSocialImages::class,
        Commands\MakeTheme::class,
        Commands\Migrate::class,
    ];

    protected $fieldtypes = [
        Fieldtypes\AdvancedSeoFieldtype::class,
        Fieldtypes\CascadeFieldtype::class,
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

    protected $updateScripts = [
        Updates\CreateSocialImagesTheme::class,
        Updates\MigrateSiteNamePosition::class,
    ];

    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
        'hotFile' => __DIR__.'/../resources/dist/hot',
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
            ->bootCascade()
            ->bootGraphQL()
            ->autoPublishConfig();
    }

    protected function bootAddonStores(): self
    {
        $seoStore = app(SeoStore::class)->directory(config('advanced-seo.directory'));

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
        Permission::extend(function () {
            Permission::group('advanced-seo', 'Advanced SEO', function () {
                Defaults::enabled()->groupBy('type')->each(function ($items, $group) {
                    Permission::register("view seo {$group} defaults", function ($permission) use ($group, $items) {
                        $permission
                            ->label('View '.ucfirst($group))
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

    protected function bootCascade(): self
    {
        $views = [
            ...Arr::wrap(config('advanced-seo.view_composer', '*')),
            'advanced-seo::head',
            'advanced-seo::body',
            'social_images.*',
        ];

        View::composer($views, function ($view) {
            $data = new Context($view->getData());

            if (! ShouldProcessViewCascade::handle($data)) {
                return;
            }

            $view->with('seo', ViewCascade::from($data)->toAugmentedArray());
        });

        return $this;
    }

    protected function bootGraphQL(): self
    {
        if (config('statamic.graphql.enabled') && config('advanced-seo.graphql')) {
            GraphQL::addQuery(SeoDefaultsQuery::class);
            GraphQL::addQuery(SeoMetaQuery::class);
            GraphQL::addQuery(SeoSitemapsQuery::class);

            GraphQL::addType(AnalyticsDefaultsType::class);
            GraphQL::addType(ComputedMetaDataType::class);
            GraphQL::addType(ContentDefaultsType::class);
            GraphQL::addType(FaviconsDefaultsType::class);
            GraphQL::addType(GeneralDefaultsType::class);
            GraphQL::addType(HreflangType::class);
            GraphQL::addType(IndexingDefaultsType::class);
            GraphQL::addType(RawMetaDataType::class);
            GraphQL::addType(RenderedViewsType::class);
            GraphQL::addType(SeoDefaultsType::class);
            GraphQL::addType(SeoMetaType::class);
            GraphQL::addType(SeoSitemapsType::class);
            GraphQL::addType(SeoSitemapType::class);
            GraphQL::addType(SiteDefaultsType::class);
            GraphQL::addType(SitemapAlternatesType::class);
            GraphQL::addType(SocialImagePresetType::class);
            GraphQL::addType(SocialMediaDefaultsType::class);

            GraphQL::addField(EntryInterface::NAME, 'seo', fn () => (new SeoField())->toArray());
            GraphQL::addField(TermInterface::NAME, 'seo', fn () => (new SeoField())->toArray());
        }

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
