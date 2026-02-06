<?php

namespace Aerni\AdvancedSeo\Context;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Enums\Scope;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Statamic;
use Statamic\Tags\Context as ViewContext;

class ContextResolver
{
    public static function resolve(mixed $model): ?Context
    {
        if ($model instanceof Context) {
            return $model;
        }

        return Blink::once('advanced-seo::context::'.spl_object_id($model), function () use ($model) {
            $resolver = new self;

            if (! $parent = $resolver->parent($model)) {
                return null;
            }

            return new Context(
                parent: $parent,
                type: $resolver->type($parent),
                handle: $resolver->handle($parent),
                scope: $resolver->scope($model),
                site: $resolver->site($model),
            );
        });
    }

    protected function parent(mixed $model): mixed
    {
        return match (true) {
            $model instanceof SeoSet => $model,
            $model instanceof SeoSetConfig => $model,
            $model instanceof SeoSetLocalization => $model,
            $model instanceof Collection => $model,
            $model instanceof Entry => $model->collection(),
            $model instanceof EntryBlueprintFound => CollectionFacade::find(
                Str::after($model->blueprint->namespace(), '.')
            ),
            $model instanceof Taxonomy => $model,
            $model instanceof Term => $model->taxonomy(),
            $model instanceof TermBlueprintFound => TaxonomyFacade::find(
                Str::after($model->blueprint->namespace(), '.')
            ),
            $model instanceof ViewContext => $this->parentFromViewContext($model),
            default => null,
        };
    }

    protected function parentFromViewContext(ViewContext $context): mixed
    {
        if ($context->value('is_entry')) {
            return $context->value('collection');
        }

        if ($context->value('is_term')) {
            return $context->value('taxonomy');
        }

        // Taxonomy index page (listing terms)
        if ($context->get('terms') instanceof TermQueryBuilder) {
            return $context->get('handle')->augmentable();
        }

        return null;
    }

    protected function type(mixed $parent): string
    {
        return match (true) {
            $parent instanceof SeoSet => $parent->type(),
            $parent instanceof SeoSetConfig => $parent->type(),
            $parent instanceof SeoSetLocalization => $parent->type(),
            $parent instanceof Collection => 'collections',
            $parent instanceof Taxonomy => 'taxonomies',
            default => throw new \InvalidArgumentException('Cannot extract type from parent'),
        };
    }

    protected function handle(mixed $parent): string
    {
        return match (true) {
            $parent instanceof SeoSet => $parent->handle(),
            $parent instanceof SeoSetConfig => $parent->handle(),
            $parent instanceof SeoSetLocalization => $parent->handle(),
            $parent instanceof Collection => $parent->handle(),
            $parent instanceof Taxonomy => $parent->handle(),
            default => throw new \InvalidArgumentException('Cannot extract handle from parent'),
        };
    }

    protected function scope(mixed $model): Scope
    {
        return match (true) {
            $model instanceof SeoSetConfig => Scope::CONFIG,
            $model instanceof SeoSetLocalization => Scope::LOCALIZATION,
            default => Scope::CONTENT,
        };
    }

    protected function site(mixed $model): ?string
    {
        return match (true) {
            $model instanceof Entry => $model->locale(),
            $model instanceof Term => $this->termSite($model),
            $model instanceof ViewContext => $model->get('site')?->handle() ?? Site::current()->handle(),
            $model instanceof SeoSet => $this->seoSetSite($model),
            $model instanceof SeoSetConfig => $model->seoSet()->selectedSite(),
            $model instanceof SeoSetLocalization => $model->locale(),
            $model instanceof Collection => $this->collectionSite(),
            $model instanceof EntryBlueprintFound => $this->cpSite(),
            $model instanceof Taxonomy => $this->cpSite(),
            $model instanceof TermBlueprintFound => $this->cpSite(),
            default => null,
        };
    }

    protected function seoSetSite(SeoSet $model): string
    {
        return request()->get('site') ?? $model->selectedSite();
    }

    protected function collectionSite(): string
    {
        // If we're not in the CP, simply return the current locale.
        if (! Statamic::isCpRoute()) {
            return Site::current()->handle();
        }

        // If we're creating an entry, get the locale from the path.
        if (Str::contains(request()->path(), 'create')) {
            return basename(request()->path());
        }

        // If we're editing an existing entry, get the locale of the entry.
        if ($entry = EntryFacade::find(basename(request()->path()))) {
            return $entry->locale();
        }

        // Return the selected site if no locale has been evaluated so far.
        return Site::selected()->handle();
    }

    protected function termSite(Term $term): string
    {
        return Statamic::isCpRoute()
            ? request()->route('site')?->handle() ?? basename(request()->path())
            : $term->locale();
    }

    protected function cpSite(): string
    {
        return Statamic::isCpRoute()
            ? request()->route('site')?->handle() ?? basename(request()->path())
            : Site::current()->handle();
    }
}
