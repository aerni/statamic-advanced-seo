<?php

use Aerni\AdvancedSeo\Rules\ValidRedirectDestination;
use Illuminate\Support\Facades\Validator;

function destinationFormatPasses(string $value): bool
{
    return Validator::make(
        ['destination' => $value],
        ['destination' => [new ValidRedirectDestination]]
    )->passes();
}

it('passes for a site-relative path', function () {
    expect(destinationFormatPasses('/about'))->toBeTrue();
});

it('passes for a nested site-relative path', function () {
    expect(destinationFormatPasses('/blog/old'))->toBeTrue();
});

it('passes for a valid absolute url', function () {
    expect(destinationFormatPasses('https://google.com'))->toBeTrue();
});

it('passes for an entry reference', function () {
    expect(destinationFormatPasses('entry::123'))->toBeTrue();
});

it('fails for an asset reference', function () {
    expect(destinationFormatPasses('asset::assets::document.pdf'))->toBeFalse();
});

it('fails for a first child reference', function () {
    expect(destinationFormatPasses('@child'))->toBeFalse();
});

it('passes for a relative path with capture placeholders', function () {
    expect(destinationFormatPasses('/p/$1'))->toBeTrue();
});

it('passes for an absolute url with capture placeholders', function () {
    expect(destinationFormatPasses('https://example.com/$1'))->toBeTrue();
});

it('fails for a relative path without a leading slash', function () {
    expect(destinationFormatPasses('about'))->toBeFalse();
});

it('fails for a url with a malformed scheme', function () {
    expect(destinationFormatPasses('https//google.com'))->toBeFalse();
});

it('fails for a url with a wrong scheme separator', function () {
    expect(destinationFormatPasses('https;//google.com'))->toBeFalse();
});

it('fails for a url with a single-slash scheme', function () {
    expect(destinationFormatPasses('https:/google.com'))->toBeFalse();
});

it('fails for a malformed absolute url', function () {
    expect(destinationFormatPasses('http://'))->toBeFalse();
});
