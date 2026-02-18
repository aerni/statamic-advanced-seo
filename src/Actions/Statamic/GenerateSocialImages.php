<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Statamic\Actions\Action;
use Statamic\Contracts\Entries\Entry;

class GenerateSocialImages extends Action
{
    protected string $icon = 'assets';

    /**
     * Determine if the current thing is an entry and if it's opted in to the auto generation config (global).
     *
     * @return bool
     */
    public function visibleTo($item)
    {
        return $item instanceof Entry && SocialImagesGenerator::enabled(Context::from($item));
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
        $items->each(fn ($item) => defer(fn () => GenerateSocialImagesJob::dispatch($item)));
    }
}
