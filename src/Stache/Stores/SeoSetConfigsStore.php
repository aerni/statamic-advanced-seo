<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Concerns\ParsesSeoSetPath;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class SeoSetConfigsStore extends BasicStore
{
    use ParsesSeoSetPath;

    public function key(): string
    {
        return 'seo-set-configs';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        if ($file->getExtension() !== 'yaml') {
            return false;
        }

        if (substr_count($filename, '/') !== 1) {
            return false;
        }

        return $this->isValidSeoSet($filename);
    }

    public function makeItemFromFile($path, $contents): SeoSetConfig
    {
        ['type' => $type, 'handle' => $handle] = $this->parseRelativePath(Str::after($path, $this->directory()));

        $data = YAML::file($path)->parse();

        return SeoConfig::make()
            ->initialPath($path)
            ->seoSet("{$type}::{$handle}")
            ->enabled(Arr::get($data, 'enabled', true))
            ->editable(Arr::get($data, 'editable', true))
            ->origins(Arr::get($data, 'origins', []))
            ->data(Arr::except($data, ['enabled', 'editable', 'origins']));
    }
}
