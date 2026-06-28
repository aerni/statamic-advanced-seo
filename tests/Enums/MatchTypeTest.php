<?php

use Aerni\AdvancedSeo\Enums\MatchType;

it('infers an exact match from a plain path', function () {
    expect(MatchType::fromSource('/old'))->toBe(MatchType::Exact);
});

it('infers an exact match from an empty source', function () {
    expect(MatchType::fromSource(''))->toBe(MatchType::Exact);
});

it('infers a wildcard match from a source containing an asterisk', function () {
    expect(MatchType::fromSource('/blog/*'))->toBe(MatchType::Wildcard);
});

it('infers a regex match from a hash-delimited source', function () {
    expect(MatchType::fromSource('#^/p/(\d+)$#'))->toBe(MatchType::Regex);
});

it('treats a hash-delimited source as regex even when it contains an asterisk', function () {
    expect(MatchType::fromSource('#^/blog/.*$#'))->toBe(MatchType::Regex);
});

it('returns a non-empty label for each match type', function () {
    foreach (MatchType::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});

it('returns the exact label for a plain path source', function () {
    expect(MatchType::fromSource('/old')->label())->toBe(MatchType::Exact->label());
});

it('returns the wildcard label for a source containing an asterisk', function () {
    expect(MatchType::fromSource('/blog/*')->label())->toBe(MatchType::Wildcard->label());
});

it('returns the regex label for a hash-delimited source', function () {
    expect(MatchType::fromSource('#^/x$#')->label())->toBe(MatchType::Regex->label());
});
