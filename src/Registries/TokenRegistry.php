<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Tokens\Normalizers\BardTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\MarkdownTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\TextareaTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\TextTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\UsersTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Aerni\AdvancedSeo\Tokens\ValueToken;
use Aerni\AdvancedSeo\Tokens\ValueTokens\SeparatorToken;
use Aerni\AdvancedSeo\Tokens\ValueTokens\SiteNameToken;
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
            BardTokenNormalizer::class,
            MarkdownTokenNormalizer::class,
            TextTokenNormalizer::class,
            TextareaTokenNormalizer::class,
            UsersTokenNormalizer::class,
            SeparatorToken::class,
            SiteNameToken::class,
            ...config('advanced-seo.tokens', []),
            ...app('advanced-seo.tokens'),
        ])
            ->filter(fn (mixed $class) => is_subclass_of($class, TokenNormalizer::class) || is_subclass_of($class, ValueToken::class))
            ->map(fn (string $class) => app($class))
            ->values()
            ->all();
    }
}
