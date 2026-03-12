<?php

use Aerni\AdvancedSeo\Registries\TokenRegistry;
use Aerni\AdvancedSeo\Tokens\Normalizers\BardTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\MarkdownTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\TextareaTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\TextTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\UsersTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\ValueToken;
use Aerni\AdvancedSeo\Tokens\ValueTokens\SeparatorToken;
use Aerni\AdvancedSeo\Tokens\ValueTokens\SiteNameToken;
use Statamic\Facades\Blink;
use Statamic\Fields\Value;

// --- Test stubs ---

class RegistryTestNormalizerOne extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'text';
    }

    public function normalize(Value $value): ?string
    {
        return 'one';
    }
}

class RegistryTestNormalizerTwo extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'text';
    }

    public function normalize(Value $value): ?string
    {
        return 'two';
    }
}

class RegistryTestNormalizerMarkdown extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'markdown';
    }

    public function normalize(Value $value): ?string
    {
        return 'markdown';
    }
}

class RegistryTestValueToken extends ValueToken
{
    public function handle(): string
    {
        return 'registry_test';
    }

    public function display(): string
    {
        return 'Registry Test';
    }

    public function value(): string
    {
        return 'test_value';
    }
}

beforeEach(function () {
    Blink::flush();
});

// --- Built-in normalizers ---

it('registers all built-in normalizers', function () {
    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->keys()->sort()->values()->all())
        ->toBe(['bard', 'markdown', 'text', 'textarea', 'users']);
});

it('resolves built-in normalizers to the correct classes', function () {
    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->get('text'))->toBeInstanceOf(TextTokenNormalizer::class)
        ->and($registry->normalizers()->get('textarea'))->toBeInstanceOf(TextareaTokenNormalizer::class)
        ->and($registry->normalizers()->get('markdown'))->toBeInstanceOf(MarkdownTokenNormalizer::class)
        ->and($registry->normalizers()->get('bard'))->toBeInstanceOf(BardTokenNormalizer::class)
        ->and($registry->normalizers()->get('users'))->toBeInstanceOf(UsersTokenNormalizer::class);
});

// --- Built-in tokens ---

it('registers all built-in value tokens', function () {
    $registry = app(TokenRegistry::class);

    expect($registry->tokens()->has('separator'))->toBeTrue()
        ->and($registry->tokens()->has('site_name'))->toBeTrue();
});

it('resolves built-in tokens to the correct classes', function () {
    $registry = app(TokenRegistry::class);

    expect($registry->tokens()->get('separator'))->toBeInstanceOf(SeparatorToken::class)
        ->and($registry->tokens()->get('site_name'))->toBeInstanceOf(SiteNameToken::class);
});

// --- Config additions ---

it('registers custom normalizers from config', function () {
    config(['advanced-seo.tokens' => [RegistryTestNormalizerOne::class]]);

    Blink::flush();

    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->get('text'))->toBeInstanceOf(RegistryTestNormalizerOne::class);
});

it('allows custom normalizers to override built-in ones', function () {
    config(['advanced-seo.tokens' => [RegistryTestNormalizerOne::class]]);

    Blink::flush();

    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->get('text'))->toBeInstanceOf(RegistryTestNormalizerOne::class)
        ->and($registry->normalizers()->get('text')->normalize(new Value('anything')))->toBe('one')
        ->and($registry->normalizers()->get('bard'))->toBeInstanceOf(BardTokenNormalizer::class);
});

it('uses the last configured normalizer for duplicate fieldtypes', function () {
    config(['advanced-seo.tokens' => [RegistryTestNormalizerOne::class, RegistryTestNormalizerTwo::class]]);

    Blink::flush();

    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->get('text'))->toBeInstanceOf(RegistryTestNormalizerTwo::class)
        ->and($registry->normalizers()->get('text')->normalize(new Value('hello')))->toBe('two');
});

it('registers custom value tokens from config', function () {
    config(['advanced-seo.tokens' => [RegistryTestValueToken::class]]);

    Blink::flush();

    $registry = app(TokenRegistry::class);

    expect($registry->tokens()->has('registry_test'))->toBeTrue()
        ->and($registry->tokens()->get('registry_test'))->toBeInstanceOf(RegistryTestValueToken::class);
});

it('filters out invalid classes from config', function () {
    config(['advanced-seo.tokens' => ['NotARealClass', \stdClass::class, RegistryTestNormalizerOne::class]]);

    Blink::flush();

    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->get('text'))->toBeInstanceOf(RegistryTestNormalizerOne::class)
        ->and($registry->normalizers())->toHaveCount(5);
});

// --- Null lookup ---

it('returns null when looking up an unsupported fieldtype', function () {
    $registry = app(TokenRegistry::class);

    expect($registry->normalizers()->get('unknown'))->toBeNull()
        ->and($registry->normalizers()->get(''))->toBeNull();
});
