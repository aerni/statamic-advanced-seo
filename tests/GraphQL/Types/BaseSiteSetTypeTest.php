<?php

use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\BaseSiteSetType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('resolves data from SeoSetLocalization using SeoSetLocalizationResolver', function () {
    Seo::find('site::general')
        ->inDefaultSite()
        ->set('site_name', 'Test Site')
        ->save();

    clearStache();

    $localization = Seo::find('site::general')->inDefaultSite();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'site_name';

    $result = (new TestSiteSet)->fields()['site_name']['resolve']($localization, [], null, $info);

    expect($result)->toBe('Test Site');
});

class TestSiteSet extends BaseSiteSetType
{
    const NAME = 'testSiteSet';

    protected $attributes = [
        'name' => self::NAME,
    ];

    protected function blueprint(): string
    {
        return GeneralBlueprint::class;
    }
}
