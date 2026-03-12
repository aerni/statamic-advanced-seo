<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Aerni\AdvancedSeo\Tokens\ValueToken;
use Illuminate\Support\Collection;

class TokenRegistry extends Registry
{
    public function normalizers(): Collection
    {
        return $this->all()
            ->filter(fn (mixed $item) => $item instanceof TokenNormalizer)
            ->keyBy(fn (TokenNormalizer $normalizer) => $normalizer->fieldtype());
    }

    public function tokens(): Collection
    {
        return $this->all()
            ->filter(fn (mixed $item) => $item instanceof ValueToken)
            ->keyBy(fn (ValueToken $token) => $token->handle());
    }

    protected function items(): array
    {
        return collect([
            \Aerni\AdvancedSeo\Tokens\Normalizers\BardTokenNormalizer::class,
            \Aerni\AdvancedSeo\Tokens\Normalizers\MarkdownTokenNormalizer::class,
            \Aerni\AdvancedSeo\Tokens\Normalizers\TextTokenNormalizer::class,
            \Aerni\AdvancedSeo\Tokens\Normalizers\TextareaTokenNormalizer::class,
            \Aerni\AdvancedSeo\Tokens\Normalizers\UsersTokenNormalizer::class,
            \Aerni\AdvancedSeo\Tokens\ValueTokens\SeparatorToken::class,
            \Aerni\AdvancedSeo\Tokens\ValueTokens\SiteNameToken::class,
            ...config('advanced-seo.tokens', []),
        ])
            ->filter(fn (mixed $class) => is_subclass_of($class, TokenNormalizer::class) || is_subclass_of($class, ValueToken::class))
            ->map(fn (string $class) => app($class))
            ->values()
            ->all();
    }
}
