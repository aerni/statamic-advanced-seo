<?php

namespace Aerni\AdvancedSeo\Tokens;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

abstract class ValueToken implements Token
{
    protected mixed $parent = null;

    abstract public function value(): ?string;

    public function display(): string
    {
        $key = "advanced-seo::tokens.{$this->handle()}";

        return Lang::has($key)
            ? __($key)
            : Str::title(str_replace(['_', '-'], ' ', $this->handle()));
    }

    public function group(): string
    {
        return __('advanced-seo::tokens.group_common');
    }

    public function withParent(mixed $parent): static
    {
        $clone = clone $this;
        $clone->parent = $parent;

        return $clone;
    }

    public function toArray(): array
    {
        return [
            'handle' => $this->handle(),
            'display' => $this->display(),
            'group' => $this->group(),
            'value' => $this->value(),
        ];
    }

    public static function register(): void
    {
        app('advanced-seo.tokens')->push(static::class);
    }
}
