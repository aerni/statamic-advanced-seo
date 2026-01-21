<?php

use Aerni\AdvancedSeo\GraphQL\Fields\SiteSetField;
use Aerni\AdvancedSeo\GraphQL\Types\SiteSetType;
use GraphQL\Type\Definition\ResolveInfo;

it('returns the SiteSetType', function () {
    expect((new SiteSetField)->type()->name)->toBe(SiteSetType::NAME);
});

it('has site argument', function () {
    expect((new SiteSetField)->args())->toHaveKey('site');
});

it('resolves by returning the args', function () {
    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'site';
    $args = ['site' => 'german'];

    $result = invade(new SiteSetField)->resolve(null, $args, null, $info);

    expect($result)->toBe($args);
});

it('resolves with empty args', function () {
    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'site';
    $args = [];

    $result = invade(new SiteSetField)->resolve(null, $args, null, $info);

    expect($result)->toBe($args);
});
