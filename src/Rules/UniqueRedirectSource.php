<?php

namespace Aerni\AdvancedSeo\Rules;

use Aerni\AdvancedSeo\Facades\Redirect;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueRedirectSource implements ValidationRule
{
    public function __construct(public string $site, public ?string $exceptId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $source = Redirect::make()->source($value)->source();

        $existing = Redirect::query()
            ->where('site', $this->site)
            ->where('source', $source)
            ->get()
            ->first(fn ($redirect) => $redirect->source() === $source);

        if ($existing && $existing->id() !== $this->exceptId) {
            $fail(__('advanced-seo::validation.redirect_source_not_unique'))->translate();
        }
    }
}
