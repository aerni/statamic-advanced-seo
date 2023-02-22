<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Statamic\Actions\Action;
use Statamic\Contracts\Entries\Entry;

class GenerateSocialImages extends Action
{
    /**
     * Determine if the current thing is an entry and if it's opted in to the auto generation config (global).
     *
     * @return bool
     */
    public function visibleTo($item)
    {
        return $item instanceof Entry && SocialImagesGenerator::enabled(GetDefaultsData::handle($item));
    }

    /**
     * Determine if the current user is allowed to run this action.
     *
     * @return bool
     */
    public function authorize($user, $item)
    {
        return $user->can('edit', $item);
    }

    /**
     * Run the action
     *
     * @return void
     */
    public function run($items, $values)
    {
        $items->each(fn ($item) => GenerateSocialImagesJob::dispatch($item));

        return config('queue.default') === 'sync'
            ? __('advanced-seo::messages.social_images_generator_generated')
            : __('advanced-seo::messages.social_images_generator_generating_queue');
    }
}
