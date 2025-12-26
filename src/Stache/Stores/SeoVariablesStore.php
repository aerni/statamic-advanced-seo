<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\SeoVariables;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class SeoVariablesStore extends BasicStore
{
    public function key(): string
    {
        return 'seo-variables';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        return substr_count($filename, '/') === 2
            && $file->getExtension() === 'yaml';
    }

    public function makeItemFromFile($path, $contents): SeoVariables
    {
        $relative = Str::after($path, $this->directory());
        [$type, $locale, $file] = explode('/', $relative);
        $handle = pathinfo($file, PATHINFO_FILENAME);

        return app(SeoVariables::class)
            ->seoSet("{$type}::{$handle}")
            ->locale($locale)
            ->initialPath($path)
            ->data(YAML::file($path)->parse());
    }

    protected function storeIndexes(): array
    {
        return [
            'type',
            'handle',
        ];
    }
}
