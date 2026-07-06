<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Actions\Action;

class DisableRedirect extends Action
{
    protected string $icon = 'eye-slash';

    public static function title()
    {
        return __('advanced-seo::messages.disable');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect && $item->enabled();
    }

    public function visibleToBulk($items)
    {
        if ($items->whereInstanceOf(Redirect::class)->count() !== $items->count()) {
            return false;
        }

        return $items->filter->enabled()->isNotEmpty();
    }

    public function authorize($user, $item)
    {
        return $user->can('manage', $item);
    }

    public function confirmationText()
    {
        return __('advanced-seo::messages.action_disable_confirmation');
    }

    public function buttonText()
    {
        return __('advanced-seo::messages.action_disable_button');
    }

    public function run($items, $values)
    {
        $items->each(fn ($redirect) => $redirect->enabled(false)->save());
    }
}
