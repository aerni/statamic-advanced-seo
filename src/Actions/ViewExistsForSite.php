<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Concerns\AsAction;
use Illuminate\View\ViewFinderInterface;
use InvalidArgumentException;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;

class ViewExistsForSite
{
    use AsAction;

    public function handle(string $template, string $site): bool
    {
        $finder = view()->getFinder();
        $key = md5(serialize([
            spl_object_id($finder),
            $finder->getPaths(),
            $finder->getHints(),
            $finder->getExtensions(),
            Site::all()->keys()->all(),
        ]));

        $finder = Blink::once("advanced-seo::views::{$site}::{$key}", function () use ($finder, $site): ViewFinderInterface {
            $finder = clone $finder;
            $finder->flush();
            $finder->setPaths($this->pathsForSite($finder->getPaths(), $site));

            foreach ($finder->getHints() as $namespace => $paths) {
                $finder->replaceNamespace($namespace, $this->pathsForSite($paths, $site));
            }

            return $finder;
        });

        try {
            $finder->find($template);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Rebuild site paths from shared roots, ignoring paths injected by Statamic's AddViewPaths middleware.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    protected function pathsForSite(array $paths, string $site): array
    {
        $paths = array_map(fn (string $path) => rtrim($path, '/\\'), $paths);

        return collect($paths)
            ->reject(fn (string $path) => Site::all()->has(basename($path)) && in_array(dirname($path), $paths, true))
            ->flatMap(fn (string $path) => [$path.'/'.$site, $path])
            ->values()
            ->all();
    }
}
