<?php

use Aerni\AdvancedSeo\Tokens\FieldToken;
use Aerni\AdvancedSeo\Tokens\Token;
use Statamic\Facades\Blink;
use Statamic\Fields\Field;

beforeEach(function () {
    Blink::flush();
});

it('implements Token', function () {
    $field = new Field('title', ['type' => 'text', 'display' => 'Title']);
    $token = new FieldToken($field);

    expect($token)->toBeInstanceOf(Token::class);
});

it('returns the field handle', function () {
    $field = new Field('title', ['type' => 'text', 'display' => 'Title']);
    $token = new FieldToken($field);

    expect($token->handle())->toBe('title');
});

it('returns the field display name', function () {
    $field = new Field('title', ['type' => 'text', 'display' => 'Title']);
    $token = new FieldToken($field);

    expect($token->display())->toBe('Title');
});

it('returns the fields group', function () {
    $field = new Field('title', ['type' => 'text', 'display' => 'Title']);
    $token = new FieldToken($field);

    expect($token->group())->toBe('Fields');
});

it('returns the correct toArray shape', function () {
    $field = new Field('title', ['type' => 'text', 'display' => 'Title']);
    $token = new FieldToken($field);

    expect($token->toArray())->toBe([
        'handle' => 'title',
        'display' => 'Title',
        'group' => 'Fields',
    ]);
});
