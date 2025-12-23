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

        $data = YAML::file($path)->parse();

        $set = Seo::make()
            ->handle($handle)
            ->type($type)
            ->data(Arr::except($data, 'title'))
            ->initialPath($path);

        $set->sites()
            ->map(fn ($site) => $this->makeVariables($set, $site))
            ->filter()
            ->each(fn ($variables) => $set->addLocalization($variables));

        return $set;
    }

    // TODO: Maybe we can ensure the localizations here instead of the controllers?
    protected function makeVariables(SeoDefaultSet $set, string $site): ?SeoVariables
    {
        $variables = $set->makeLocalization($site);

        if (! File::exists($path = $variables->path())) {
            return null;
        }

        $parsed = YAML::file($path)->parse();

        // TODO: Can we remove this stuff?
        // TODO: We shouldn't store the origin in the SeoVariables file anymore.
        // It now comes from the SeoDefaultsSet file. Should we adapt the origin() method?

        // New format with config and data sections (for backward compatibility during migration)
        if (isset($parsed['config']) && isset($parsed['data'])) {
            $parsedData = $parsed['data'];
            $origin = Arr::get($parsed['config'], 'origin');
        }
        // New flat format
        else {
            $parsedData = Arr::except($parsed, 'origin');
            $origin = Arr::get($parsed, 'origin');
        }

        return $variables
            ->initialPath($path)
            ->merge($parsedData)
            ->origin($set->get('sites')[$site] ?? null);
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
