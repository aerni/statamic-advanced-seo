<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class SeoSetLocalizationsStore extends BasicStore
{
    public function key(): string
    {
        return 'seo-set-localizations';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        return substr_count($filename, '/') === 2
            && $file->getExtension() === 'yaml';
    }

    public function makeItemFromFile($path, $contents): SeoSetLocalization
    {
        $relative = Str::after($path, $this->directory());
        [$type, $locale] = explode('/', $relative);
        $handle = pathinfo($path, PATHINFO_FILENAME);

        return SeoLocalization::make("{$type}::{$handle}", $locale)
            ->initialPath($path)
            ->data(YAML::file($path)->parse());
    }

    protected function storeIndexes(): array
    {
        return ['seoSet'];
    }
}
