<?php

namespace Aerni\AdvancedSeo\Stache;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\ChildStore;
use Symfony\Component\Finder\SplFileInfo;

class SeoDefaultsStore extends ChildStore
{
    public function makeItemFromFile($path, $contents)
    {
        [$type, $locale, $handle] = $this->extractAttributesFromPath($path);

        $data = YAML::file($path)->parse($contents);

        $seo = Seo::make()
            ->type($type)
            ->handle($handle)
            ->locale($locale)
            ->data($data)
            ->initialPath($path);

        return $seo;
    }

    protected function extractAttributesFromPath($path): array
    {
        $locale = Site::default()->handle();
        $relativePath = str_after($path, $this->directory());
        $type = pathinfo($relativePath, PATHINFO_DIRNAME);
        $handle = pathinfo($path, PATHINFO_FILENAME);

        if (Site::hasMultiple()) {
            [$type, $locale] = explode('/', $type);
        }

        return [$type, $locale, $handle];
    }

    public function filter(SplFileInfo $file)
    {
        return $file->getExtension() === 'yaml';
    }
}
