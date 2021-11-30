<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Entries\Entry;

class GenerateSocialImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Entry $entry;

    // TODO: Have to make this work with Taxonomies as well.
    public function __construct(Entry $entry)
    {
        $this->entry = $entry;
        $this->queue = config('advanced-seo.social_images.generator.queue');
    }

    public function handle()
    {
        $images = $this->generateNewImages();

        $this->deleteOldImages();

        $this->entry->merge($images)->saveQuietly();
    }

    protected function generateNewImages(): array
    {
        return SocialImage::make()->entry($this->entry)->generate()->toArray();
    }

    protected function deleteOldImages(): void
    {
        $oldImages = [
            $this->entry->get('seo_og_image'),
            $this->entry->get('seo_twitter_image'),
        ];

        foreach ($oldImages as $key => $path) {
            File::delete(SocialImage::make()->container()->disk()->path($path));
        }
    }
}
