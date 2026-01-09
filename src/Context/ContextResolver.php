<?php

namespace Aerni\AdvancedSeo\Context;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Enums\Scope;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Fields\Value;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Statamic;
use Statamic\Tags\Context as TagsContext;

class ContextResolver
{
    public function __construct(protected mixed $model)
    {
        //
    }

    public function resolve(): ?Context
    {
        if ($this->model instanceof Context) {
            return $this->model;
        }

        if (! $parent = $this->parent($this->model)) {
            return null;
        }

        return new Context(
            type: $this->type($parent),
            handle: $this->handle($parent),
            scope: $this->scope(),
            site: $this->site($this->model),
        );
    }

    protected function parent(mixed $model): mixed
    {
        return match (true) {
            $model instanceof SeoSet => $model,
            $model instanceof Collection => $model,
            $model instanceof Entry => $model->collection(),
            $model instanceof EntryBlueprintFound => CollectionFacade::find(
                Str::after($model->blueprint->namespace(), '.')
            ),
            $model instanceof TagsContext && $model->get('collection') instanceof Value
                => $model->get('collection')->value(),
            $model instanceof Taxonomy => $model,
            $model instanceof Term => $model->taxonomy(),
            $model instanceof TermBlueprintFound => TaxonomyFacade::find(
                Str::after($model->blueprint->namespace(), '.')
            ),
            $model instanceof TagsContext && $model->get('taxonomy') instanceof Value
                => $model->get('taxonomy')->value(),
            $model instanceof TagsContext && $model->get('terms') instanceof TermQueryBuilder
                => TaxonomyFacade::find($model->get('handle')->value()),
            default => null,
        };
    }

    protected function type(mixed $parent): string
    {
        return match (true) {
            $parent instanceof SeoSet => $parent->type(),
            $parent instanceof Collection => 'collections',
            $parent instanceof Taxonomy => 'taxonomies',
            default => throw new \InvalidArgumentException('Cannot extract type from parent'),
        };
    }

    protected function handle(mixed $parent): string
    {
        return match (true) {
            $parent instanceof SeoSet => $parent->handle(),
            $parent instanceof Collection => $parent->handle(),
            $parent instanceof Taxonomy => $parent->handle(),
            default => throw new \InvalidArgumentException('Cannot extract handle from parent'),
        };
    }

    protected function scope(): Scope
    {
        return Scope::CONTENT;
    }

    protected function site(mixed $model): ?string
    {
        return match (true) {
            $model instanceof Entry => $model->locale(),
            $model instanceof Term => $this->termSite($model),
            $model instanceof TagsContext => $model->get('site')?->handle() ?? Site::current()->handle(),
            $model instanceof Context => $model->site,
            $model instanceof SeoSet => $this->seoSetSite($model),
            $model instanceof Collection => $this->collectionSite(),
            $model instanceof EntryBlueprintFound => $this->CpSite(),
            $model instanceof Taxonomy => $this->CpSite(),
            $model instanceof TermBlueprintFound => $this->CpSite(),
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
            ? basename(request()->path())
            : $term->locale();
    }

    protected function CpSite(): string
    {
        return Statamic::isCpRoute()
            ? basename(request()->path())
            : Site::current()->handle();
    }
}
