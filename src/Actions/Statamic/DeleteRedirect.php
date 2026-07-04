<?php

namespace Aerni\AdvancedSeo\Actions\Statamic;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Actions\Action;

class DeleteRedirect extends Action
{
    protected $dangerous = true;

    protected string $icon = 'trash';

    public static function title()
    {
        return __('Delete');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect;
    }

    public function authorize($user, $item)
    {
        return $user->can('delete', $item);
    }

    public function confirmationText()
    {
        return __('advanced-seo::messages.action_delete_confirmation');
    }

    public function buttonText()
    {
        return __('advanced-seo::messages.action_delete_button');
    }

    public function bypassesDirtyWarning(): bool
    {
        return true;
    }

    public function run($items, $values)
    {
        $items->each->delete();
    }

    public function redirect($items, $values)
    {
        if ($this->context['view'] !== 'form') {
            return;
        }

        return cp_route('advanced-seo.redirects.index');
    }
}
