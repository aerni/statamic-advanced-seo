<?php

use Aerni\AdvancedSeo\Tokens\Normalizers\BardTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\MarkdownTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\TextareaTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\TextTokenNormalizer;
use Aerni\AdvancedSeo\Tokens\Normalizers\UsersTokenNormalizer;
use Statamic\Contracts\Query\Builder;
use Statamic\Fields\Value;

// --- Text ---

it('normalizes text values by stripping tags', function () {
    $normalizer = new TextTokenNormalizer;

    expect($normalizer->normalize(new Value('<strong>Hello</strong>')))->toBe('Hello');
});

it('returns empty string for null text values', function () {
    $normalizer = new TextTokenNormalizer;

    expect($normalizer->normalize(new Value(null)))->toBe('');
});

it('returns empty string for empty text values', function () {
    $normalizer = new TextTokenNormalizer;

    expect($normalizer->normalize(new Value('')))->toBe('');
});

it('strips multiple nested tags from text', function () {
    $normalizer = new TextTokenNormalizer;

    expect($normalizer->normalize(new Value('<div><p>Hello <em>world</em></p></div>')))->toBe('Hello world');
});

it('reports its fieldtype as text', function () {
    expect((new TextTokenNormalizer)->fieldtype())->toBe('text');
});

// --- Textarea ---

it('normalizes textarea values by stripping tags', function () {
    $normalizer = new TextareaTokenNormalizer;

    expect($normalizer->normalize(new Value('<strong>Hello</strong>')))->toBe('Hello');
});

it('returns empty string for null textarea values', function () {
    $normalizer = new TextareaTokenNormalizer;

    expect($normalizer->normalize(new Value(null)))->toBe('');
});

it('returns empty string for empty textarea values', function () {
    $normalizer = new TextareaTokenNormalizer;

    expect($normalizer->normalize(new Value('')))->toBe('');
});

it('reports its fieldtype as textarea', function () {
    expect((new TextareaTokenNormalizer)->fieldtype())->toBe('textarea');
});

// --- Markdown ---

it('normalizes markdown values to plain text', function () {
    $normalizer = new MarkdownTokenNormalizer;

    expect($normalizer->normalize(new Value('<p>Hello <em>world</em></p>')))->toBe('Hello world');
});

it('returns empty string for empty markdown values', function () {
    $normalizer = new MarkdownTokenNormalizer;

    expect($normalizer->normalize(new Value('')))->toBe('')
        ->and($normalizer->normalize(new Value(null)))->toBe('');
});

it('trims whitespace from normalized markdown', function () {
    $normalizer = new MarkdownTokenNormalizer;

    expect($normalizer->normalize(new Value('  <p>Hello</p>  ')))->toBe('Hello');
});

it('reports its fieldtype as markdown', function () {
    expect((new MarkdownTokenNormalizer)->fieldtype())->toBe('markdown');
});

// --- Bard ---

it('normalizes bard values to plain text', function () {
    $normalizer = new BardTokenNormalizer;

    expect($normalizer->normalize(new Value('<p>Hello <strong>world</strong></p>')))->toBe('Hello world');
});

it('returns empty string for empty bard values', function () {
    $normalizer = new BardTokenNormalizer;

    expect($normalizer->normalize(new Value('')))->toBe('');
});

it('extracts strings from nested bard arrays', function () {
    $normalizer = new BardTokenNormalizer;

    $bardData = [
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'First paragraph'],
        ]],
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'Second paragraph'],
        ]],
    ];

    $value = Mockery::mock(Value::class);
    $value->shouldReceive('value')->andReturn('');
    $value->shouldReceive('raw')->andReturn($bardData);

    $result = $normalizer->normalize($value);

    expect($result)->toContain('First paragraph')
        ->and($result)->toContain('Second paragraph');
});

it('reports its fieldtype as bard', function () {
    expect((new BardTokenNormalizer)->fieldtype())->toBe('bard');
});

// --- Users ---

it('normalizes a single user value', function () {
    $normalizer = new UsersTokenNormalizer;
    $user = new class
    {
        public function name(): string
        {
            return 'Jane Doe';
        }
    };

    expect($normalizer->normalize(new Value($user)))->toBe('Jane Doe');
});

it('normalizes a user query builder to a sentence list', function () {
    $normalizer = new UsersTokenNormalizer;

    $userOne = new class
    {
        public function name(): string
        {
            return 'Alice';
        }
    };

    $userTwo = new class
    {
        public function name(): string
        {
            return 'Bob';
        }
    };

    $collection = collect([$userOne, $userTwo]);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('limit')->with(5)->andReturnSelf();
    $builder->shouldReceive('get')->andReturn($collection);

    expect($normalizer->normalize(new Value($builder)))->toBe('Alice and Bob');
});

it('returns empty string for a user with an empty name', function () {
    $normalizer = new UsersTokenNormalizer;
    $user = new class
    {
        public function name(): string
        {
            return '';
        }
    };

    expect($normalizer->normalize(new Value($user)))->toBe('');
});

it('filters out users with empty names from query builder results', function () {
    $normalizer = new UsersTokenNormalizer;

    $userWithName = new class
    {
        public function name(): string
        {
            return 'Alice';
        }
    };

    $userWithoutName = new class
    {
        public function name(): ?string
        {
            return null;
        }
    };

    $collection = collect([$userWithName, $userWithoutName]);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('limit')->with(5)->andReturnSelf();
    $builder->shouldReceive('get')->andReturn($collection);

    expect($normalizer->normalize(new Value($builder)))->toBe('Alice');
});

it('returns empty string for an empty query builder result', function () {
    $normalizer = new UsersTokenNormalizer;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('limit')->with(5)->andReturnSelf();
    $builder->shouldReceive('get')->andReturn(collect());

    expect($normalizer->normalize(new Value($builder)))->toBe('');
});

it('reports its fieldtype as users', function () {
    expect((new UsersTokenNormalizer)->fieldtype())->toBe('users');
});
