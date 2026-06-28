<?php

namespace Aerni\AdvancedSeo\Rules;

use Aerni\AdvancedSeo\Facades\Redirects;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class UniqueRedirectSource implements ValidationRule
{
    public function __construct(public ?string $site = null, public ?string $exceptId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $site = $this->site ?: Site::default()->handle();

        $existing = Redirects::query()
            ->where('site', $site)
            ->where('source', Str::lower((string) $value))
            ->first();

        if ($existing && $existing->id() !== $this->exceptId) {
            $fail(__('advanced-seo::messages.redirect_source_not_unique'))->translate();
        }
    }
}
