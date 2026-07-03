<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\RedirectHit;
use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class RedirectHitsStore extends BasicStore
{
    public function key(): string
    {
        return 'redirect-hits';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        if ($file->getExtension() !== 'yaml') {
            return false;
        }

        return Str::substrCount($filename, '/') === 0;
    }

    public function makeItemFromFile($path, $contents): RedirectHit
    {
        $data = YAML::file($path)->parse();

        return Redirects::hits()->make()
            ->initialPath($path)
            ->redirect(pathinfo($path, PATHINFO_FILENAME))
            ->count(Arr::get($data, 'count', 0))
            ->lastHitAt(Arr::get($data, 'last_hit_at'));
    }

    protected function storeIndexes(): array
    {
        return ['id', 'redirect'];
    }
}
