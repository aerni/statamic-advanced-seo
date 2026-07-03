<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Statamic\Actions\Action;

class ResetRedirectHits extends Action
{
    protected string $icon = 'history';

    public static function title()
    {
        return __('advanced-seo::messages.action_reset_hits');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect && config('advanced-seo.redirects.hits.enabled');
    }

    public function visibleToBulk($items)
    {
        return config('advanced-seo.redirects.hits.enabled')
            && $items->whereInstanceOf(Redirect::class)->count() === $items->count();
    }

    public function authorize($user, $item)
    {
        return $user->can('edit', $item);
    }

    public function confirmationText()
    {
        return __('advanced-seo::messages.action_reset_hits_confirmation');
    }

    public function buttonText()
    {
        return __('advanced-seo::messages.action_reset_hits_button');
    }

    public function run($items, $values)
    {
        $items->each(fn ($redirect) => RedirectFacade::hits()->find($redirect->id())?->delete());
    }
}
