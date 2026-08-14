<?php

namespace Aerni\AdvancedSeo\Rules;

use Aerni\AdvancedSeo\Enums\SourceType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRedirectSource implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('advanced-seo::validation.redirect_source_invalid'))->translate();

            return;
        }

        if (SourceType::fromSource($value) === SourceType::Regex) {
            if (@preg_match($value, '') === false) {
                $fail(__('advanced-seo::validation.redirect_source_invalid_regex'))->translate();
            }

            return;
        }

        if (! str_starts_with($value, '/')) {
            $fail(__('advanced-seo::validation.redirect_source_invalid'))->translate();
        }
    }
}
