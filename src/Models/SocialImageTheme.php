<?php

namespace Aerni\AdvancedSeo\Models;

use Illuminate\Support\Str;
use Statamic\Facades\Folder;
use Illuminate\Support\Collection;

class SocialImageTheme extends Model
{
    protected static function getRows(): array
    {
        return Folder::disk('resources')
            ->getFolders('views/social_images')
            ->map(function ($path) {
                $handle = Str::of($path)->basename()->snake()->jsonSerialize();
                $title = Str::of($handle)->replace('_', ' ')->title()->jsonSerialize();

                $templates = Folder::disk('resources')->getFiles($path)
                    ->mapWithKeys(fn ($template) => [Str::of($template)->basename('.antlers.html')->jsonSerialize() => $template]);

                return [
                    'handle' => $handle,
                    'title' => $title,
                    'templates' => $templates,
                ];
            })
            ->sortBy('title')
            ->values()
            ->toArray();
    }

    protected static function all(): Collection
    {
        return static::$rows;
    }

    protected static function templatesOfType(string $id): Collection
    {
        return static::$rows->mapWithKeys(function ($theme) use ($id) {
            return [$theme['handle'] => collect($theme['templates'])->first(fn ($view, $key) => $key === $id)];
        })->filter();
    }

    protected static function fieldtypeOptions(): array
    {
        return static::$rows->flatMap(fn ($theme) => [$theme['handle'] => $theme['title']])->toArray();
    }
}
