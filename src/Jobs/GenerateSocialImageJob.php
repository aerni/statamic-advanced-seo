<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\Contracts\Entries\Entry;
use Statamic\Support\Arr;

class GenerateSocialImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Entry $entry;

    public function __construct(Entry $entry)
    {
        $this->entry = $entry;
    }

    public function handle()
    {
        if (! $this->shouldGenerate()) {
            return;
        }

        $images = SocialImage::make()
            ->id($this->entry->id())
            ->basename($this->entry->slug())
            ->generate()
            ->toArray();

        $data = array_merge($this->entry->get('seo'), $images);

        $this->entry->set('seo', $data)->saveQuietly();

        // TODO: Check if this is really needed. It might be when the job is queued.
        // Stache::clear();
    }

    protected function shouldGenerate(): bool
    {
        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // TODO: Make this work with new Stache Store. And do we really need this functionality?
        // // Get the collections that are allowed to generate social images.
        // $enabledCollections = Storage::inSelectedSite()
        //     ->get('general')
        //     ->get('social_images_generator_collections', []);

        // // Shouldn't generate if the entry's collection is not selected.
        // if (! in_array($this->entry->collection()->handle(), $enabledCollections)) {
        //     return false;
        // }

        // Shouldn't generate if the entry's generator toggle is off.
        if (! Arr::get($this->entry->get('seo'), 'generate_social_images')) {
            return false;
        }

        return true;
    }
}
