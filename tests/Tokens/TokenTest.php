<?php

use Aerni\AdvancedSeo\Tokens\Token as TokenInterface;
use Aerni\AdvancedSeo\Tokens\ValueToken;
use Statamic\Facades\Blink;

beforeEach(function () {
    Blink::flush();
});

// --- ValueToken subclass ---

it('implements Token', function () {
    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'custom';
        }

        public function value(): ?string
        {
            return 'custom_value';
        }
    };

    expect($token)->toBeInstanceOf(ValueToken::class)
        ->and($token)->toBeInstanceOf(TokenInterface::class);
});

it('auto-derives display from handle', function () {
    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'company_name';
        }

        public function value(): ?string
        {
            return null;
        }
    };

    expect($token->display())->toBe('Company Name');
});

it('returns the common group', function () {
    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'test';
        }

        public function value(): ?string
        {
            return null;
        }
    };

    expect($token->group())->toBe('Common');
});

it('returns the correct toArray shape', function () {
    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'company_name';
        }

        public function value(): ?string
        {
            return 'Acme Corp';
        }
    };

    expect($token->toArray())->toBe([
        'handle' => 'company_name',
        'display' => 'Company Name',
        'group' => 'Common',
        'value' => 'Acme Corp',
    ]);
});

// --- withParent() ---

it('clones the token when calling withParent()', function () {
    $parent = new stdClass;

    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'test';
        }

        public function value(): ?string
        {
            return 'static';
        }
    };

    expect($token->withParent($parent))->not->toBe($token);
});

it('does not mutate the original token when calling withParent()', function () {
    $parent = new stdClass;
    $parent->name = 'Parent';

    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'author';
        }

        public function value(): ?string
        {
            return $this->parent?->name ?? 'none';
        }
    };

    $token->withParent($parent);

    expect($token->value())->toBe('none');
});

it('passes parent context via withParent()', function () {
    $parent = new stdClass;
    $parent->siteName = 'My Site';

    $token = new class extends ValueToken
    {
        public function handle(): string
        {
            return 'site';
        }

        public function value(): ?string
        {
            return $this->parent?->siteName ?? '';
        }
    };

    expect($token->withParent($parent)->value())->toBe('My Site');
});

