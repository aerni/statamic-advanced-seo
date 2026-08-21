<?php

use Aerni\AdvancedSeo\Enums\RedirectSourceType;

it('infers an exact match from a plain path', function () {
    expect(RedirectSourceType::fromSource('/old'))->toBe(RedirectSourceType::Exact);
});

it('infers an exact match from an empty source', function () {
    expect(RedirectSourceType::fromSource(''))->toBe(RedirectSourceType::Exact);
});

it('infers a wildcard match from a source containing an asterisk', function () {
    expect(RedirectSourceType::fromSource('/blog/*'))->toBe(RedirectSourceType::Wildcard);
});

it('infers a regex match from a hash-delimited source', function () {
    expect(RedirectSourceType::fromSource('#^/p/(\d+)$#'))->toBe(RedirectSourceType::Regex);
});

it('treats a hash-delimited source as regex even when it contains an asterisk', function () {
    expect(RedirectSourceType::fromSource('#^/blog/.*$#'))->toBe(RedirectSourceType::Regex);
});
