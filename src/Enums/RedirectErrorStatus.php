<?php

namespace Aerni\AdvancedSeo\Enums;

use Aerni\AdvancedSeo\Contracts\Redirect;

enum RedirectErrorStatus: string
{
    case Handled = 'handled';
    case Disabled = 'disabled';
    case Unhandled = 'unhandled';

    /**
     * Derive the status of an error from the redirect that covers it.
     */
    public static function for(?Redirect $redirect): self
    {
        if ($redirect === null) {
            return self::Unhandled;
        }

        return $redirect->enabled() ? self::Handled : self::Disabled;
    }

    public function label(): string
    {
        return __("advanced-seo::messages.redirect_error_status_{$this->value}");
    }
}
