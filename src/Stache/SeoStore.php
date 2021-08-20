<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Facades\File;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Stache\Stores\BasicStore;
use Symfony\Component\Finder\SplFileInfo;

class SeoStore extends BasicStore
{
    public function key()
    {
        return 'seo';
    }

    public function getItemFilter(SplFileInfo $file)
    {
        return $file->getExtension() === 'yaml';
    }

    public function makeItemFromFile($path, $contents)
    {
        $data = YAML::file($path)->parse($contents);

        return Site::hasMultiple()
            ? $this->makeMultiSiteDefaultFromFile($path)
            : $this->makeSingleSiteDefaultFromFile($path, $data);
    }

    protected function makeBaseDefaultFromFile($path)
    {
        [$type, $handle] = $this->extractAttributesFromPath($path);

        return Seo::make()
            ->handle($handle)
            ->type($type);
    }

    protected function makeSingleSiteDefaultFromFile($path, $data)
    {
        $set = $this->makeBaseDefaultFromFile($path);

        $localization = $set->makeLocalization(Site::default()->handle())
            ->initialPath($path)
            ->data($data);

        return $set->addLocalization($localization);
    }

    protected function makeMultiSiteDefaultFromFile($path)
    {
        $set = $this->makeBaseDefaultFromFile($path);

        Site::all()->filter(function ($site) use ($set) {
            return File::exists("{$this->directory}{$set->type()}/{$site->handle()}/{$set->handle()}.yaml");
        })->map->handle()->map(function ($site) use ($set) {
            return $this->makeVariables($set, $site);
        })->filter()->each(function ($variables) use ($set) {
            $set->addLocalization($variables);
        });

        return $set;
    }

    protected function makeVariables($set, $site)
    {
        $variables = $set->makeLocalization($site);

        // todo: cache the reading and parsing of the file
        if (! File::exists($path = $variables->path())) {
            return;
        }

        $data = YAML::file($path)->parse();

        $variables
            ->initialPath($path)
            ->data(Arr::except($data, 'origin'));

        if ($origin = Arr::get($data, 'origin')) {
            $variables->origin($origin);
        }

        return $variables;
    }

    protected function extractAttributesFromPath($path): array
    {
        $relative = str_after($path, $this->directory);
        $type = str_before($relative, '/');
        $handle = pathinfo($relative, PATHINFO_FILENAME);

        return [$type, $handle];
    }

    public function save($set)
    {
        $set->localizations()->each(function ($localization) {
            parent::save($localization);
        });
    }

    public function getItemKey($item)
    {
        return "{$item->type()}::{$item->handle()}";
    }
}
