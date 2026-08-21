<?php

namespace Aerni\AdvancedSeo\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Statamic\Support\Str;

class ValidRedirectDestination implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (Str::startsWith($value, 'entry::')) {
            return;
        }

        if (Str::startsWith($value, '/')) {
            return;
        }

        if (Validator::make(['url' => $value], ['url' => 'url:http,https'])->fails()) {
            $fail(__('advanced-seo::validation.redirect_destination_invalid'))->translate();
        }
    }
}
