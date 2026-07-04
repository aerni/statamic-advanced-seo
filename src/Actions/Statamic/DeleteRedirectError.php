<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Statamic\Actions\Action;
use Statamic\Facades\Site;

class DeleteRedirectError extends Action
{
    protected $dangerous = true;

    protected string $icon = 'trash';

    public static function title()
    {
        return __('Delete');
    }

    public function visibleTo($item)
    {
        return $item instanceof RedirectError;
    }

    public function visibleToBulk($items)
    {
        return $items->whereInstanceOf(RedirectError::class)->count() === $items->count();
    }

    public function authorize($user, $item)
    {
        return $user->can('delete redirects')
            && $user->can('view', Site::get($item->site()));
    }

    public function confirmationText()
    {
        return __('advanced-seo::messages.action_delete_error_confirmation');
    }

    public function buttonText()
    {
        return __('advanced-seo::messages.action_delete_button');
    }

    public function run($items, $values)
    {
        $items->each->delete();
    }
}
