<?php

namespace Aerni\AdvancedSeo\Cascades;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

class ContentViewCascade extends ContentCascade
{
    protected function process(): self
    {
        return parent::process()->sanitizeStrings();
    }

    public function computedKeys(): Collection
    {
        return parent::computedKeys()->merge(['prev_url', 'next_url']);
    }

    public function canonical(): ?string
    {
        $baseUrl = parent::canonical();

        if (! $baseUrl || ! request()->has('page') || ! Blink::get('tag-paginator')) {
            return $baseUrl;
        }

        $page = request()->integer('page');

        // Don't include the pagination parameter on the first page.
        // We don't want the same site to be indexed with and without parameter.
        return $page === 1 ? $baseUrl : "{$baseUrl}?page={$page}";
    }

    public function prevUrl(): ?string
    {
        if (! $paginator = $this->paginator()) {
            return null;
        }

        $page = $paginator->currentPage();

        return match (true) {
            $page === 2 => $this->canonicalUrl(),
            $page > 2 && $page <= $paginator->lastPage() => $this->paginatedUrl($page - 1),
            default => null,
        };
    }

    public function nextUrl(): ?string
    {
        if (! $paginator = $this->paginator()) {
            return null;
        }

        return $paginator->currentPage() < $paginator->lastPage()
            ? $this->paginatedUrl($paginator->currentPage() + 1)
            : null;
    }

    protected function paginator(): ?LengthAwarePaginator
    {
        return $this->isIndexable($this->model) ? Blink::get('tag-paginator') : null;
    }

    protected function paginatedUrl(int $page): string
    {
        return $this->canonicalUrl()."?page={$page}";
    }
}
