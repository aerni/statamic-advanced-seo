<?php

namespace Aerni\AdvancedSeo\Rules;

use Aerni\AdvancedSeo\Enums\SourceType;
use Aerni\AdvancedSeo\Redirects\RedirectPatternMatcher;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class NonCircularRedirectDestination implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $destination, Closure $fail): void
    {
        $source = Arr::get($this->data, 'source');

        if (! is_string($destination) || $destination === '' || ! is_string($source) || $source === '') {
            return;
        }

        if (SourceType::fromSource($source) !== SourceType::Exact) {
            return;
        }

        $sourcePath = $this->normalizedPath($source);

        if ($this->destinationPath($destination) === $sourcePath) {
            $fail(__('advanced-seo::messages.redirect_destination_circular'))->translate();
        }
    }

    /**
     * Resolve the destination to a site-relative path for comparison.
     * Returns null when the destination lives on another host or site.
     */
    protected function destinationPath(string $destination): ?string
    {
        if (Str::startsWith($destination, '/')) {
            return $this->normalizedPath($destination);
        }

        $url = match (true) {
            Str::startsWith($destination, 'entry::') => Entry::find(Str::after($destination, 'entry::'))?->absoluteUrl(),
            Str::startsWith($destination, ['http://', 'https://']) => $destination,
            default => null,
        };

        if (! $url) {
            return null;
        }

        // The site field only exists on multisite installs; fall back to the default site otherwise.
        $site = Site::get(Arr::get($this->data, 'site')) ?? Site::default();

        if (parse_url($url, PHP_URL_HOST) !== parse_url($site->absoluteUrl(), PHP_URL_HOST)) {
            return null;
        }

        return $this->normalizedPath($site->relativePath($url));
    }

    /**
     * Normalize to a lowercase, single-slash path so exact sources and
     * destinations compare case-insensitively.
     */
    protected function normalizedPath(string $path): string
    {
        return Str::lower(RedirectPatternMatcher::normalizePath($path));
    }
}
