<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Blink;

class Theme
{
    public readonly string $handle;

    public readonly string $title;

    public function __construct(public readonly string $path)
    {
        $this->handle = str($path)->basename()->snake();
        $this->title = str($path)->basename()->headline();
    }

    public function templates(): Collection
    {
        return Blink::once("advanced-seo.themes.{$this->handle}.templates", function () {
            return collect(File::files($this->path))
                ->mapWithKeys(function ($file) {
                    $key = str($file)->basename('.antlers.html')->toString();
                    $view = "social_images/{$this->handle}/{$key}";

                    return [$key => $view];
                });
        });
    }

    public function template(string $type): ?string
    {
        return $this->templates()->get($type);
    }
}
