<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Site;
use Statamic\Sites\Site as StatamicSite;

class Cascade
{
    protected StatamicSite $site;
    protected Collection $context;
    protected Collection $data;

    public function __construct(Collection $context)
    {
        $this->site = Site::current();
        $this->context = $context;
        $this->data = $this->data();
    }

    public static function make(Collection $context): self
    {
        return new static($context);
    }

    // TODO: I need a smart way of handling default settings when they are booleans.
    public function get(): Collection
    {
        return $this->computedContext();
    }

    public function data(): Collection
    {
        return $this->defaultsOfType('site')
            ->merge($this->contentDefaults())
            ->merge($this->onPageSeo())
            ->mapWithKeys(function ($item, $key) {
                return [Str::remove('seo_', $key) => $item];
            });
    }

    protected function computedContext(): Collection
    {
        // Remove all seo variables from context.
        $contextWithoutSeoVariables = $this->context->filter(function ($value, $key) {
            return ! Str::contains($key, 'seo_');
        });

        $seoVariables = $this->data->merge($this->computedData())->all();

        // Return new context with all seo variables in an seo key.
        return $contextWithoutSeoVariables->merge(['seo' => $seoVariables]);
    }

    protected function computedData(): array
    {
        return [
            'compiled_title' => $this->compiledTitle(),
            'locale' => $this->locale(),
        ];
    }

    protected function onPageSeo(): Collection
    {
        return $this->context->filter(function ($value, $key) {
            return Str::contains($key, 'seo_');
        })->filter(function ($item) {
            return $item->raw();
        });
    }

    protected function defaultsOfType(string $type): Collection
    {
        return Seo::allOfType($type)->flatMap(function ($defaults) {
            return $defaults->in($this->site->handle())->toAugmentedArray();
        });
    }

    protected function contentDefaults(): ?Collection
    {
        $parent = $this->context->get('collection') ?? $this->context->get('taxonomy');

        if ($parent instanceof \Statamic\Entries\Collection) {
            return $this->defaultsOfType('collections');
        }

        if ($parent instanceof \Statamic\Taxonomies\Taxonomy) {
            return $this->defaultsOfType('taxonomies');
        }

        return null;
    }

    protected function compiledTitle(): string
    {
        return "{$this->title()} {$this->titleSeparator()} {$this->siteName()}";
    }

    protected function title(): string
    {
        return $this->data->get('title') ?? $this->data->get('title');
    }

    protected function titleSeparator(): string
    {
        return $this->data->get('title_separator') ?? '|';
    }

    protected function siteName(): string
    {
        return $this->data->get('site_name') ?? config('app.name');
    }

    protected function locale(): string
    {
        return $this->site->locale();
    }
}
