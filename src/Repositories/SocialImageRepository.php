<?php

namespace Aerni\AdvancedSeo\Repositories;

use Spatie\Browsershot\Browsershot;
use Statamic\Facades\GlobalSet;
use Illuminate\Support\Facades\File;
use Statamic\Facades\AssetContainer;
use Aerni\AdvancedSeo\Facades\SeoGlobals;
use Statamic\Entries\Entry;

class SocialImageRepository
{
    public function make(Entry $entry)
    {
        File::ensureDirectoryExists(AssetContainer::find('seo')->disk()->path('social_images'));

        $ogTemplateUrl = config('app.url') . '/seo/social-images/og/' . $entry->id();
        $ogPath = 'social_images/' . $entry->slug() . '-og.png';

        $twitterTemplateUrl = config('app.url') . '/seo/social-images/twitter/' . $entry->id();
        $twitterPath = 'social_images/' . $entry->slug() . '-twitter.png';

        Browsershot::url($ogTemplateUrl)
            ->windowSize(1200, 630)
            ->save(AssetContainer::find('seo')->disk()->path($ogPath));

        Browsershot::url($twitterTemplateUrl)
            ->windowSize(1200, 600)
            ->save(AssetContainer::find('seo')->disk()->path($twitterPath));

        $entry
            ->set('og_image', $ogPath)
            ->set('twitter_image', $twitterPath)
            ->saveQuietly();
    }

    public function shouldGenerate(Entry $entry): bool
    {
        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        $globals = GlobalSet::find(SeoGlobals::handle())
            ->inSelectedSite()
            ->data()
            ->get('social_images_collections', []);

        // Shouldn't generate if the entry's collection is not selected.
        if (! in_array($entry->collection()->handle(), $globals)) {
            return false;
        }

        // Shouldn't generate if the entry's generator toggle is off.
        if (! $entry->get('generate_social_images')) {
            return false;
        }

        return true;
    }
}
