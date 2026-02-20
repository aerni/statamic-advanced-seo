<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Fields\SiteSetField;
use Aerni\AdvancedSeo\GraphQL\Types\SiteSetType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Facades\Site;

it('returns the SiteSetType', function () {
    expect((new SiteSetField)->type()->name)->toBe(SiteSetType::NAME);
});

it('has site argument', function () {
    expect((new SiteSetField)->args())->toHaveKey('site');
});

it('resolves via SeoSetResolver', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'site';

    $result = invade(new SiteSetField)->resolve(null, ['site' => 'english'], null, $info);

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);
    expect($result->handle())->toBe('defaults');
});

it('resolves to default site when no site argument is provided', function () {
    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'site';

    $result = invade(new SiteSetField)->resolve(null, [], null, $info);

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);
    expect($result->handle())->toBe('defaults');
});
