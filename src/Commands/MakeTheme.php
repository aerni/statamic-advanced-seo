<?php

namespace Aerni\AdvancedSeo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

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
        $source = __DIR__.'/../../resources/stubs/social_images/';
        $target = resource_path('views/social_images/');
        $layout = 'layout.antlers.html';

        if (! File::exists($target.$layout)) {
            File::ensureDirectoryExists($target);
            File::copy($source.$layout, $target.$layout);
            info("Created layout: {$this->getRelativePath($target.$layout)}");
        }
    }

    protected function publishTheme(): void
    {
        $theme = $this->argument('name') ?? text(
            label: 'What do you want to call the theme?',
            placeholder: 'default',
            required: true,
        );

        $source = __DIR__.'/../../resources/stubs/social_images/templates';
        $target = resource_path('views/social_images/'.$theme);

        if (File::exists($target) && ! confirm("A theme with the name {$theme} already exists. Do you want to overwrite it?")) {
            return;
        }

        File::ensureDirectoryExists($target);
        File::copyDirectory($source, $target);
        info("Created theme: {$this->getRelativePath($target)}");
    }

    protected function getRelativePath($path): string
    {
        return str_replace(base_path().'/', '', $path);
    }
}
