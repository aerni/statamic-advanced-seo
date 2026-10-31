<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Concerns\ResolvesTaxonomyUrls;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class AutomaticTaxonomyRedirects
{
    use ResolvesTaxonomyUrls;

    /** @var array<string, array<string, array{taxonomy: string, site: string, collection: string, path: string}>> */
    private array $paths = [];

    /** @var array<string, list<array{site: string, path: string}>> */
    private array $deleting = [];

    public function __construct(private AutomaticRedirectWriter $redirects) {}

    public function stashAffected(AffectedUrls $change): void
    {
        foreach ($change->mounts as $mount) {
            $this->stashMount($change->key, $mount['site'], $mount['collections'], $mount['path']);
        }
    }

    public function createAffected(AffectedUrls $change): void
    {
        $key = $change->key;
        $paths = $this->paths[$key] ?? [];
        unset($this->paths[$key]);

        if ($paths === [] || ! RedirectsFeature::enabled()) {
            return;
        }

        // Empty collections trigger no entry URI rewrite to clear these caches.
        $this->forgetPathCaches();

        foreach ($paths as $stashed) {
            $taxonomy = TaxonomyFacade::find($stashed['taxonomy']);
            $collection = CollectionFacade::findByHandle($stashed['collection']);

            if (! $taxonomy || ! $collection || ! $this->redirectsEnabled($taxonomy)) {
                continue;
            }

            $scoped = (clone $taxonomy)->collection($collection);

            if (! $currentPath = $this->taxonomyIndexPath($scoped, $stashed['site'])) {
                continue;
            }

            $this->redirects->deleteBySource($currentPath, $stashed['site']);

            if ($stashed['path'] !== $currentPath) {
                $this->redirects->create($stashed['path'], $currentPath, $stashed['site']);
            }
        }
    }

    /** @param Collection<int, Taxonomy> $scopes */
    public function stashDeletion(string $key, Collection $scopes, ?string $site = null): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $paths = [];

        foreach ($scopes as $scope) {
            foreach ($site === null ? $scope->sites() : [$site] as $locale) {
                $paths[] = [
                    'site' => $locale,
                    'path' => $this->taxonomyIndexPath($scope, $locale, onlyPublicUrls: false),
                ];
            }
        }

        $this->deleting[$key] = collect($paths)->unique(fn (array $path) => "{$path['site']}|{$path['path']}")->values()->all();
    }

    public function deleteAffected(string $key): void
    {
        $paths = $this->deleting[$key] ?? [];
        unset($this->deleting[$key]);

        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($paths as $path) {
            if (! $this->redirects->hasPublicOwner($path['path'], $path['site'])) {
                $this->redirects->deleteByDestination($path['path'], $path['site']);
            }
        }
    }

    /**
     * Capture public indexes of mounted collections, including empty ones.
     *
     * @param  Collection<int, StatamicCollection>  $collections
     */
    private function stashMount(string $key, string $site, Collection $collections, ?string $mountPath): void
    {
        if (! RedirectsFeature::enabled() || $mountPath === null) {
            return;
        }

        foreach ($collections as $collection) {
            foreach ($collection->taxonomies() as $taxonomy) {
                if (! $this->redirectsEnabled($taxonomy)) {
                    continue;
                }

                $scoped = (clone $taxonomy)->collection($collection);

                if ($path = $this->taxonomyIndexPath($scoped, $site, $mountPath)) {
                    $this->paths[$key]["{$site}|{$collection->handle()}|{$taxonomy->handle()}"] ??= [
                        'taxonomy' => $taxonomy->handle(),
                        'site' => $site,
                        'collection' => $collection->handle(),
                        'path' => $path,
                    ];
                }
            }
        }
    }

    private function forgetPathCaches(): void
    {
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('collection-mounts')->flush();
    }

    private function redirectsEnabled(Taxonomy $taxonomy): bool
    {
        return (bool) Seo::find("taxonomies::{$taxonomy->handle()}")?->config()->value('redirects');
    }
}
