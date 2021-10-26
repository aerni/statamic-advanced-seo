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
    }

    public function handle()
    {
        if (! $this->shouldGenerate()) {
            return;
        }

        $images = $this->generateNewImages();

        $this->deleteOldImages();

        $this->entry->merge($images)->saveQuietly();
    }

    protected function generateNewImages(): array
    {
        return SocialImage::make()
            ->id($this->entry->id())
            ->basename($this->entry->slug())
            ->generate()
            ->toArray();
    }

    protected function deleteOldImages(): void
    {
        $oldImages = [
            $this->entry->get('seo_og_image'),
            $this->entry->get('seo_twitter_image')
        ];

        foreach ($oldImages as $key => $path) {
            File::delete(SocialImage::make()->container()->disk()->path($path));
        }
    }

    protected function shouldGenerate(): bool
    {
        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Get the collections that are allowed to generate social images.
        $enabledCollections = Seo::find('site', 'general')
            ->in($this->entry->site()->handle())
            ->value('social_images_generator_collections') ?? [];

        // Shouldn't generate if the entry's collection is not selected.
        if (! in_array($this->entry->collection()->handle(), $enabledCollections)) {
            return false;
        }

        $enabledByDefault = Seo::find('collections', $this->entry->collection()->handle())
            ->in($this->entry->site()->handle())
            ->value('seo_generate_social_images');

        $enabledOnEntry = $this->entry->get('seo_generate_social_images');

        $enabled = $enabledOnEntry ?? $enabledByDefault;

        // Shouldn't generate if the entry's generator toggle is off.
        if (! $enabled) {
            return false;
        }

        return true;
    }
}
