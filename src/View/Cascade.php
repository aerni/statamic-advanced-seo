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
        $data = $this->defaultsOfType('site')
            ->merge($this->contentDefaults())
            ->merge($this->onPageSeo())
            ->mapWithKeys(function ($item, $key) {
                return [Str::remove('seo_', $key) => $item];
            })
            ->sortKeys();

        return $this->ensureOverrides($data);
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
            'title' => $this->compiledTitle(),
            'og_title' => $this->ogTitle(),
            'og_description' => $this->ogDescription(),
            'twitter_title' => $this->twitterTitle(),
            'twitter_description' => $this->twitterDescription(),
            'indexing' => $this->indexing(),
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

    protected function ensureOverrides($data): Collection
    {
        $siteDefaults = $this->defaultsOfType('site');

        return $data->merge([
            'noindex' => $siteDefaults->get('noindex')->value() ?: $data->get('noindex')->value(),
            'nofollow' => $siteDefaults->get('nofollow')->value() ?: $data->get('nofollow')->value(),
        ]);
    }

    protected function compiledTitle(): string
    {
        return "{$this->title()} {$this->titleSeparator()} {$this->siteName()}";
    }

    protected function title(): string
    {
        return $this->data->get('title') ?? $this->context->get('title');
    }

    protected function titleSeparator(): string
    {
        return $this->data->get('title_separator') ?? '|';
    }

    protected function siteName(): string
    {
        return $this->data->get('site_name') ?? config('app.name');
    }

    protected function ogTitle(): string
    {
        return $this->data->get('og_title') ?? $this->title();
    }

    protected function ogDescription(): ?string
    {
        return $this->data->get('og_description') ?? $this->data->get('description');
    }

    protected function twitterTitle(): string
    {
        return $this->data->get('twitter_title') ?? $this->title();
    }

    protected function twitterDescription(): ?string
    {
        return $this->data->get('twitter_description') ?? $this->data->get('description');
    }

    protected function indexing(): string
    {
        return collect([
            'noindex' => $this->data->get('noindex'),
            'nofollow' => $this->data->get('nofollow'),
        ])->filter()->keys()->implode(', ');
    }

    protected function locale(): string
    {
        return $this->site->locale();
    }
}
