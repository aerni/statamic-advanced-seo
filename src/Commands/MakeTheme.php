<?php

namespace Aerni\AdvancedSeo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;

class MakeTheme extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:theme {name?}';
    protected $description = 'Create a new social images theme';

    public function handle(): void
    {
        $this->publishTheme();
        $this->publishLayout();
    }

    protected function publishLayout(): void
    {
        $source = __DIR__ . '/../../resources/stubs/social_images/';
        $target = resource_path('views/social_images/');
        $layout = 'layout.antlers.html';

        if (! File::exists($target.$layout)) {
            File::ensureDirectoryExists($target);
            File::copy($source.$layout, $target.$layout);
            $this->line("<info>[✓]</info> The layout was successfully created: <comment>{$this->getRelativePath($target.$layout)}</comment>");
        }
    }

    protected function publishTheme(): void
    {
        $theme = $this->argument('name') ?? $this->ask('What do you want to call the theme?', 'default');

        $source = __DIR__ . '/../../resources/stubs/social_images/templates';
        $target = resource_path('views/social_images/' . $theme);

        if (! File::exists($target) || $this->confirm("A theme with the name <comment>$theme</comment> already exists. Do you want to overwrite it?")) {
            File::ensureDirectoryExists($target);
            File::copyDirectory($source, $target);
            $this->line("<info>[✓]</info> The theme was successfully created: <comment>{$this->getRelativePath($target)}</comment>");
        }
    }

    protected function getRelativePath($path): string
    {
        return str_replace(base_path() . '/', '', $path);
    }
}
