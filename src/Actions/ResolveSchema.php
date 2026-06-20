<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Cascades\SeoFieldtypeCascade;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Sites\Site as SiteInstance;
use Statamic\Support\Str;
use Statamic\Tags\Context as ViewContext;
use Statamic\View\Cascade;

class ResolveSchema
{
    /**
     * Schema-producing keys removed from the variable set so a reference to
     * them resolves to empty instead of re-augmenting.
     *
     * @var array<int, string>
     */
    protected static array $schemaKeys = ['json_ld', 'site_json_ld', 'site_schema', 'page_schema'];

    public static function handle(?string $value, Entry|Term|ViewContext $model): ?string
    {
        if ($value === null || ! Str::contains($value, '{{')) {
            return $value;
        }

        $variables = match (true) {
            $model instanceof Entry,
            $model instanceof Term => static::contentVariables($model),
            $model instanceof ViewContext => static::contextVariables($model),
        };

        return Antlers::parse($value, $variables);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function contentVariables(Entry|Term $model): array
    {
        $model = Helpers::localizedContent($model);

        return Blink::once("advanced-seo::schema-context::{$model->id()}::{$model->locale()}", function () use ($model) {
            return array_merge(
                static::baseVariables(Context::from($model), $model->site()),
                ['current_url' => $model->absoluteUrl()],
                $model->toAugmentedArray(),
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected static function contextVariables(ViewContext $model): array
    {
        $site = $model->get('site') ?? Site::current();

        return array_merge(
            static::baseVariables(Context::from($model), $site),
            $model->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function baseVariables(?Context $context, SiteInstance $site): array
    {
        $seo = $context ? SeoFieldtypeCascade::from($context)->data()->all() : [];

        $seo = collect($seo)->except(static::$schemaKeys)->all();

        return array_merge($seo, static::globals($site), ['config' => Cascade::config()]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function globals(SiteInstance $site): array
    {
        $variables = [];

        foreach (GlobalSet::all() as $set) {
            if (! $localized = $set->in($site->handle())) {
                continue;
            }

            if ($set->handle() === 'global') {
                $variables = array_merge($variables, $localized->toDeferredAugmentedArray());
            } else {
                $variables[$set->handle()] = $localized;
            }
        }

        return $variables;
    }
}
