<?php

namespace Aerni\AdvancedSeo\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Facades\Entry;
use Statamic\Support\Str;

class PublishedRedirectDestination implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Str::startsWith($value, 'entry::')) {
            return;
        }

        $entry = Entry::find(Str::after($value, 'entry::'));

        if ($entry && ! $entry->published()) {
            $fail(__('advanced-seo::validation.redirect_destination_unpublished'))->translate();
        }
    }
}
