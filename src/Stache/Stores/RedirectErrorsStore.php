<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Path;
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

    public function deleteKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $files = collect($keys)
            ->map(fn ($key) => $this->getPath($key))
            ->filter()
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
