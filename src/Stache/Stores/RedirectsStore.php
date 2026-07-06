<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
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

        return RedirectFacade::make()
            ->initialPath($path)
            ->id(pathinfo($path, PATHINFO_FILENAME))
            ->source(Arr::get($data, 'source'))
            ->destination(Arr::get($data, 'destination'))
            ->responseCode(ResponseCode::tryFrom(Arr::get($data, 'response_code', ResponseCode::Permanent->value)) ?? ResponseCode::Permanent)
            ->site(basename(dirname($path)))
            ->enabled(Arr::get($data, 'enabled', true))
            ->preserveQueryString(Arr::get($data, 'preserve_query_string', true))
            ->origin(Origin::tryFrom(Arr::get($data, 'origin', Origin::Manual->value)) ?? Origin::Manual)
            ->description(Arr::get($data, 'description'))
            ->createdAt(Arr::get($data, 'created_at'));
    }

    protected function storeIndexes(): array
    {
        return ['id', 'site', 'source', 'enabled', 'origin'];
    }
}
