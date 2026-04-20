<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Aerni\AdvancedSeo\Concerns\ParsesSeoSetPath;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Statamic\Facades\Path;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class SeoSetLocalizationsStore extends BasicStore
{
    use ParsesSeoSetPath;

    public function key(): string
    {
        return 'seo-set-localizations';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        if ($file->getExtension() !== 'yaml') {
            return false;
        }

        if (Str::substrCount($filename, '/') !== 2) {
            return false;
        }

        return $this->isValidSeoSet($filename);
    }

    public function makeItemFromFile($path, $contents): SeoSetLocalization
    {
        ['type' => $type, 'locale' => $locale, 'handle' => $handle] = $this->parseRelativePath(Str::after($path, $this->directory()));

        return SeoLocalization::make()
            ->initialPath($path)
            ->seoSet("{$type}::{$handle}")
            ->locale($locale)
            ->data(YAML::file($path)->parse());
    }

    protected function storeIndexes(): array
    {
        return ['seoSet'];
    }
}
