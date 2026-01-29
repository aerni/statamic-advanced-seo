<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Fields\TaxonomySetField;
use Aerni\AdvancedSeo\GraphQL\Types\TaxonomySetType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('returns the TaxonomySetType', function () {
    expect((new TaxonomySetField)->type()->name)->toBe(TaxonomySetType::NAME);
});

it('has handle and site arguments', function () {
    $args = (new TaxonomySetField)->args();

    expect($args)->toHaveKeys(['handle', 'site']);
    expect($args['handle']['type']->getWrappedType()->name)->toBe('String');
});

it('resolves via SeoSetResolver', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Taxonomy::make('tags')->sites(['english'])->saveQuietly();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'taxonomy';

    $result = invade(new TaxonomySetField)->resolve(null, ['handle' => 'tags'], null, $info);

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);
    expect($result->handle())->toBe('tags');
});
