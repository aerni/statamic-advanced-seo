<?php

namespace Aerni\AdvancedSeo\Rules;

use Aerni\AdvancedSeo\Facades\Redirects;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class UniqueRedirectSource implements DataAwareRule, ValidationRule
{
    public array $data = [];

    public function __construct(public ?string $exceptId = null) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $site = $this->data['site'] ?? Site::default()->handle();

        $existing = Redirects::query()
            ->where('site', $site)
            ->where('source', Str::lower((string) $value))
            ->first();

        if ($existing && $existing->id() !== $this->exceptId) {
            $fail(__('advanced-seo::messages.redirect_source_not_unique'))->translate();
        }
    }
}
