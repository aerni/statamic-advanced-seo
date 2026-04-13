<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\SeoSets\SeoData;
use Illuminate\Contracts\Support\Arrayable;
use Spatie\SchemaOrg\Schema;

it('can be created via the Seo facade', function () {
    expect(Seo::data())->toBeInstanceOf(SeoData::class);
});

it('includes seo_enabled in toArray output', function () {
    $data = new SeoData;

    expect($data->toArray())->toHaveKey('seo_enabled', true);
});

it('only includes set values', function () {
    $data = (new SeoData)->title('Login');

    $array = $data->toArray();

    expect($array)->toHaveCount(2)
        ->toHaveKey('seo_enabled', true)
        ->toHaveKey('seo_title', 'Login');
});

it('supports method chaining', function () {
    $data = (new SeoData)
        ->title('Login')
        ->description('Welcome back!')
        ->ogTitle('Login | My App')
        ->ogDescription('Welcome back to My App')
        ->noindex()
        ->nofollow();

    expect($data)->toBeInstanceOf(SeoData::class);
});

it('can set the title', function () {
    $data = (new SeoData)->title('Login');

    expect($data->toArray())->toHaveKey('seo_title', 'Login');
});

it('can set the description', function () {
    $data = (new SeoData)->description('Welcome back!');

    expect($data->toArray())->toHaveKey('seo_description', 'Welcome back!');
});

it('can set the og title', function () {
    $data = (new SeoData)->ogTitle('Login | My App');

    expect($data->toArray())->toHaveKey('seo_og_title', 'Login | My App');
});

it('can set the og description', function () {
    $data = (new SeoData)->ogDescription('Welcome back to My App');

    expect($data->toArray())->toHaveKey('seo_og_description', 'Welcome back to My App');
});

it('can set the og image with a string', function () {
    $data = (new SeoData)->ogImage('https://example.com/og.jpg');

    expect($data->toArray())->toHaveKey('seo_og_image', 'https://example.com/og.jpg');
});

it('can set noindex', function () {
    $data = (new SeoData)->noindex();

    expect($data->toArray())->toHaveKey('seo_noindex', true);
});

it('can set nofollow', function () {
    $data = (new SeoData)->nofollow();

    expect($data->toArray())->toHaveKey('seo_nofollow', true);
});

it('can set noarchive', function () {
    $data = (new SeoData)->noarchive();

    expect($data->toArray())->toHaveKey('seo_noarchive', true);
});

it('can set nosnippet', function () {
    $data = (new SeoData)->nosnippet();

    expect($data->toArray())->toHaveKey('seo_nosnippet', true);
});

it('can set noimageindex', function () {
    $data = (new SeoData)->noimageindex();

    expect($data->toArray())->toHaveKey('seo_noimageindex', true);
});

it('can set a canonical url', function () {
    $data = (new SeoData)->canonicalUrl('https://example.com/canonical');

    expect($data->toArray())
        ->toHaveKey('seo_canonical_type', 'custom')
        ->toHaveKey('seo_canonical_custom', 'https://example.com/canonical');
});

it('can set json-ld with a string', function () {
    $json = '{"@type": "WebPage", "name": "Login"}';
    $data = (new SeoData)->jsonLd($json);

    expect($data->toArray())->toHaveKey('seo_json_ld', $json);
});

it('can set json-ld with a Spatie SchemaOrg Type', function () {
    $schema = Schema::webPage()->name('Login');
    $data = (new SeoData)->jsonLd($schema);

    $expected = json_encode($schema->toArray(), JSON_UNESCAPED_UNICODE);

    expect($data->toArray())->toHaveKey('seo_json_ld', $expected);
});

it('produces the expected full output', function () {
    $data = (new SeoData)
        ->title('Login')
        ->description('Welcome back!')
        ->ogTitle('Login | My App')
        ->noindex()
        ->canonicalUrl('https://example.com/login');

    expect($data->toArray())->toBe([
        'seo_enabled' => true,
        'seo_title' => 'Login',
        'seo_description' => 'Welcome back!',
        'seo_og_title' => 'Login | My App',
        'seo_noindex' => true,
        'seo_canonical_type' => 'custom',
        'seo_canonical_custom' => 'https://example.com/login',
    ]);
});

it('implements Arrayable', function () {
    $data = (new SeoData)->title('Login');

    expect($data)->toBeInstanceOf(Arrayable::class);
});
