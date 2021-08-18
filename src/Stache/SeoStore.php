<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Stache\Stores\BasicStore;
use Symfony\Component\Finder\SplFileInfo;

class SeoStore extends BasicStore
{
    protected $storeIndexes = [
        'handle',
    ];

    public function key()
    {
        return 'seo';
    }

    public function makeItemFromFile($path, $contents)
    {
        [$type, $locale, $handle] = $this->extractAttributesFromPath($path);

        $data = YAML::file($path)->parse($contents);

        if (! $id = array_pull($data, 'id')) {
            $idGenerated = true;
            $id = app('stache')->generateId();
        }

        $seo = Seo::make()
            ->id($id)
            ->type($type)
            ->handle($handle)
            ->locale($locale)
            ->data($data)
            ->initialPath($path);

        // if (isset($idGenerated)) {
        //     $seo->save();
        // }

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
