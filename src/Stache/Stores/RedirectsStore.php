<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class RedirectsStore extends BasicStore
{
    public function key(): string
    {
        return 'redirects';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        if ($file->getExtension() !== 'yaml') {
            return false;
        }

        return Str::substrCount($filename, '/') === 1;
    }

    public function makeItemFromFile($path, $contents): Redirect
    {
        $data = YAML::file($path)->parse();

        return Redirects::make()
            ->initialPath($path)
            ->id(pathinfo($path, PATHINFO_FILENAME))
            ->source(Arr::get($data, 'source'))
            ->destination(Arr::get($data, 'destination'))
            ->type(RedirectType::tryFrom(Arr::get($data, 'type', RedirectType::Permanent->value)) ?? RedirectType::Permanent)
            ->site(basename(dirname($path)))
            ->enabled(Arr::get($data, 'enabled', true))
            ->forwardQueryString(Arr::get($data, 'forward_query_string', true))
            ->description(Arr::get($data, 'description'));
    }

    protected function storeIndexes(): array
    {
        return ['id', 'site', 'source', 'enabled'];
    }
}
