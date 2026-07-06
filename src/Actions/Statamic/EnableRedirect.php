<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Actions\Action;

class EnableRedirect extends Action
{
    protected string $icon = 'eye';

    public static function title()
    {
        return __('advanced-seo::messages.enable');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect && ! $item->enabled();
    }

    public function visibleToBulk($items)
    {
        if ($items->whereInstanceOf(Redirect::class)->count() !== $items->count()) {
            return false;
        }

        return $items->reject->enabled()->isNotEmpty();
    }

    public function authorize($user, $item)
    {
        return $user->can('manage', $item);
    }

    public function confirmationText()
    {
        return __('advanced-seo::messages.action_enable_confirmation');
    }

    public function buttonText()
    {
        return __('advanced-seo::messages.action_enable_button');
    }

    public function run($items, $values)
    {
        $items->each(fn ($redirect) => $redirect->enabled(true)->save());
    }
}
