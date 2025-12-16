<?php

namespace Aerni\AdvancedSeo\Stache;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Str;
use Statamic\Facades\File;
use Statamic\Facades\Path;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\ChildStore;
use Statamic\Support\Arr;
use Symfony\Component\Finder\SplFileInfo;

class SeoDefaultsStore extends ChildStore
{
    public function getItemFilter(SplFileInfo $file): bool
    {
        $filename = Path::tidy($file->getRelativePathname());

        // Only get the SeoDefaultSet that exists in the root. Don't get the SeoVariables.
        return substr_count($filename, '/') === 0
            && $file->getExtension() === 'yaml';
    }

    public function makeItemFromFile($path, $contents): SeoDefaultSet
    {
        [$type, $handle] = $this->extractAttributesFromPath($path);

        $set = Seo::make()
            ->handle($handle)
            ->type($type)
            ->initialPath($path);

        $set->sites()
            ->map(fn ($site) => $this->makeVariables($set, $site))
            ->filter()
            ->each(fn ($variables) => $set->addLocalization($variables));

        return $set;
    }

    protected function makeVariables(SeoDefaultSet $set, string $site): ?SeoVariables
    {
        $variables = $set->makeLocalization($site);

        if (! File::exists($path = $variables->path())) {
            return null;
        }

        $parsed = YAML::file($path)->parse();

        // New format with config and data sections
        if (isset($parsed['config']) && isset($parsed['data'])) {
            $parsedConfig = $parsed['config'];
            $parsedData = $parsed['data'];
        }
        // Legacy flat format (for backward compatibility during migration)
        else {
            $parsedConfig = Arr::only($parsed, 'origin');
            $parsedData = Arr::except($parsed, 'origin');
        }

        return $variables
            ->initialPath($path)
            ->config(fn ($config) => $config->merge($parsedConfig))
            ->merge($parsedData)
            ->origin(Arr::get($parsedConfig, 'origin'));
    }

    public function save($set): void
    {
        parent::save($set);

        Site::all()->each(function ($site) use ($set) {
            $site = $site->handle();
            $set->existsIn($site) ? $set->in($site)->writeFile() : $set->makeLocalization($site)->deleteFile();
        });
    }

    public function delete($set): void
    {
        parent::delete($set);

        $set->localizations()->each(fn ($localization) => $localization->deleteFile());
    }

    protected function extractAttributesFromPath(string $path): array
    {
        $relative = Str::after($path, $this->parent->directory());
        $type = pathinfo($relative, PATHINFO_DIRNAME);
        $handle = pathinfo($relative, PATHINFO_FILENAME);

        return [$type, $handle];
    }
}
