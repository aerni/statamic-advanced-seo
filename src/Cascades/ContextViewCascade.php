<?php

namespace Aerni\AdvancedSeo\Cascades;

use Aerni\AdvancedSeo\Concerns\EvaluatesContextType;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Facades\Site;

class ContextViewCascade extends ContentViewCascade
{
    use EvaluatesContextType;

    public function title(): string
    {
        return match (true) {
            $this->contextIsTaxonomy($this->model),
            $this->contextIsCollectionTaxonomy($this->model) => $this->fallbackTitle($this->model->get('title')),
            $this->contextIs404($this->model) => $this->fallbackTitle('404'),
            default => $this->get('title') ?? $this->model->get('title') ?? $this->siteName(),
        };
    }

    protected function fallbackTitle(string $pageTitle): string
    {
        return "{$pageTitle} {$this->get('separator')} {$this->siteName()}";
    }

    public function locale(): string
    {
        return Helpers::parseLocale(Site::current()->locale());
    }

    public function hreflang(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        return match (true) {
            $this->contextIsTaxonomy($this->model) => $this->taxonomyHreflang($this->model->get('page')),
            $this->contextIsCollectionTaxonomy($this->model) => $this->collectionTaxonomyHreflang($this->model->get('page')),
            default => null,
        };
    }

    protected function canonicalUrl(): string
    {
        return $this->model->get('current_url');
    }

    protected function siteUrl(): string
    {
        return Site::current()->absoluteUrl();
    }

    protected function siteHandle(): string
    {
        return Site::current()->handle();
    }

    protected function isHomepage(): bool
    {
        return (bool) $this->model->get('is_homepage');
    }

    protected function breadcrumbSegments(): array
    {
        return request()->segments();
    }
}
