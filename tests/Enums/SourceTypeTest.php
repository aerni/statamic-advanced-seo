<?php

use Aerni\AdvancedSeo\Enums\SourceType;

it('infers an exact match from a plain path', function () {
    expect(SourceType::fromSource('/old'))->toBe(SourceType::Exact);
});

it('infers an exact match from an empty source', function () {
    expect(SourceType::fromSource(''))->toBe(SourceType::Exact);
});

it('infers a wildcard match from a source containing an asterisk', function () {
    expect(SourceType::fromSource('/blog/*'))->toBe(SourceType::Wildcard);
});

it('infers a regex match from a hash-delimited source', function () {
    expect(SourceType::fromSource('#^/p/(\d+)$#'))->toBe(SourceType::Regex);
});

it('treats a hash-delimited source as regex even when it contains an asterisk', function () {
    expect(SourceType::fromSource('#^/blog/.*$#'))->toBe(SourceType::Regex);
});
