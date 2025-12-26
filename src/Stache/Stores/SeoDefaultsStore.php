<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\ChildStore;
use Statamic\Support\Arr;
use Symfony\Component\Finder\SplFileInfo;

class SeoDefaultsStore extends ChildStore
{
    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        return substr_count($filename, '/') === 0
            && $file->getExtension() === 'yaml';
    }

    public function makeItemFromFile($path, $contents): SeoDefaultSet
    {
        $handle = pathinfo($path, PATHINFO_FILENAME);

        $data = YAML::file($path)->parse();

        $set = Seo::make()
            ->type($this->childKey)
            ->handle($handle)
            ->initialPath($path)
            ->origins(Arr::get($data, 'origins', []));

        // Defaults of type 'site' are always enabled.
        if ($this->childKey !== 'site') {
            $set->enabled(Arr::get($data, 'enabled', true));
        }

        return $set;
    }
}
