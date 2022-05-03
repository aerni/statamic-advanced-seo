<?php

namespace Aerni\AdvancedSeo\Updates;

use Illuminate\Support\Facades\File;
use Statamic\UpdateScripts\UpdateScript;

class CreateSocialImagesTheme extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('1.1.0');
    }

    public function update()
    {
        $filesToMove = [
            'open_graph.antlers.html',
            'twitter_summary.antlers.html',
            'twitter_summary_large_image.antlers.html',
        ];

        collect($filesToMove)->each(function ($filename) {
            $filePath = resource_path("views/social_images/{$filename}");
            $directoryPath = resource_path("views/social_images/default");
            $fileTarget = resource_path("views/social_images/default/{$filename}");

            if (File::exists($filePath)) {
                File::makeDirectory($directoryPath);
                File::move($filePath, $fileTarget);
            }
        });

        $this->console()->info('Successfully created social images theme!');
    }
}
