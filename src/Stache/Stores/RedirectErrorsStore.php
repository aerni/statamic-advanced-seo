<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Path;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class RedirectErrorsStore extends BasicStore
{
    public function key(): string
    {
        return 'redirect-errors';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        if ($file->getExtension() !== 'yaml') {
            return false;
        }

        return Str::substrCount($filename, '/') === 0;
    }

    public function makeItemFromFile($path, $contents): RedirectError
    {
        $data = YAML::file($path)->parse();

        return Redirect::errors()->make()
            ->initialPath($path)
            ->id(pathinfo($path, PATHINFO_FILENAME))
            ->url(Arr::get($data, 'url'))
            ->site(Arr::get($data, 'site'))
            ->count(Arr::get($data, 'count', 0))
            ->firstSeenAt(Arr::get($data, 'first_seen_at'))
            ->lastSeenAt(Arr::get($data, 'last_seen_at'));
    }

    /**
     * Delete these error files and clear the path index.
     *
     * RedirectErrorRepository::deleteByIds() splits the ids into chunks and calls
     * this once per chunk, while holding the write lock. clear() then asks
     * paths() for the id-to-file map. paths() rebuilds that map by reading
     * every error file when it is not already in memory or in the cache. The
     * previous chunk's clear() wiped the cache, so the next chunk would do
     * that rebuild under the lock.
     *
     * The cached map is stored on $this->paths first, including when the cache
     * is empty. paths() returns that map instead of scanning. An id missing
     * from the map is still {id}.yaml in this store's directory, which is the
     * only path these records are written to.
     */
    public function deleteKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $this->paths = collect(Stache::cacheStore()->get($this->pathsCacheKey(), []));

        $files = collect($keys)
            ->map(fn ($key) => $this->paths->get($key) ?: Path::assemble($this->directory(), "{$key}.yaml"))
            ->filter()
            ->unique()
            ->values()
            ->all();

        File::delete($files);

        $this->clear();
    }

    protected function storeIndexes(): array
    {
        return ['id', 'url', 'site', 'count', 'first_seen_at', 'last_seen_at'];
    }
}
