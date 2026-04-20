<?php

use Aerni\AdvancedSeo\GraphQL\Types\SocialImagePresetType;

it('has the correct name', function () {
    expect(SocialImagePresetType::NAME)->toBe('socialImagePreset');
});

it('exposes all expected fields', function () {
    expect((new SocialImagePresetType)->fields())->toHaveKeys([
        'width',
        'height',
    ]);
});

it('resolves data', function () {
    $fields = (new SocialImagePresetType)->fields();
    $preset = ['width' => '1200', 'height' => '630'];

    expect($fields['width']['resolve']($preset))->toBe('1200');
    expect($fields['height']['resolve']($preset))->toBe('630');
});
