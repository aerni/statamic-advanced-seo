<?php

use Aerni\AdvancedSeo\Redirects\RedirectPatternMatcher;

it('does not match an exact source', function () {
    expect(RedirectPatternMatcher::match('/old', '/old'))->toBeNull();
});

it('matches a wildcard source and returns the captures', function () {
    expect(RedirectPatternMatcher::match('/blog/*', '/blog/hello'))->toBe(['/blog/hello', 'hello']);
});

it('matches multiple wildcard segments', function () {
    expect(RedirectPatternMatcher::match('/*/*', '/a/b'))->toBe(['/a/b', 'a', 'b']);
});

it('does not let a wildcard cross a slash', function () {
    expect(RedirectPatternMatcher::match('/blog/*', '/blog/a/b'))->toBeNull();
});

it('returns null when a wildcard source does not match', function () {
    expect(RedirectPatternMatcher::match('/blog/*', '/news/x'))->toBeNull();
});

it('normalizes a wildcard source before matching', function () {
    expect(RedirectPatternMatcher::match('/blog/*/', '/blog/hello'))->toBe(['/blog/hello', 'hello']);
});

it('matches a regex source and returns the captures', function () {
    expect(RedirectPatternMatcher::match('#^/p/(\d+)$#', '/p/42'))->toBe(['/p/42', '42']);
});

it('returns null when a regex source does not match', function () {
    expect(RedirectPatternMatcher::match('#^/p/(\d+)$#', '/p/abc'))->toBeNull();
});

it('returns null for a malformed regex source instead of throwing', function () {
    expect(RedirectPatternMatcher::match('#^/p/(\d+$#', '/p/42'))->toBeNull();
});

it('matches a wildcard source case-insensitively and preserves the captured case', function () {
    expect(RedirectPatternMatcher::match('/blog/*', '/Blog/Hello'))->toBe(['/Blog/Hello', 'Hello']);
});

it('keeps regex sources case-sensitive unless the author opts in', function () {
    expect(RedirectPatternMatcher::match('#^/Section$#', '/section'))->toBeNull()
        ->and(RedirectPatternMatcher::match('#^/Section$#i', '/section'))->toBe(['/section']);
});

it('substitutes capture placeholders into the destination', function () {
    expect(RedirectPatternMatcher::substitute('/news/$1', ['/blog/hello', 'hello']))->toBe('/news/hello');
});

it('substitutes multiple placeholders', function () {
    expect(RedirectPatternMatcher::substitute('/$1/$2', ['/a/b', 'a', 'b']))->toBe('/a/b');
});

it('replaces a missing capture with an empty string', function () {
    expect(RedirectPatternMatcher::substitute('/x/$2', ['/x/a', 'a']))->toBe('/x/');
});

it('leaves a destination without placeholders untouched', function () {
    expect(RedirectPatternMatcher::substitute('/static', ['/old', 'cap']))->toBe('/static');
});

it('normalizes paths to a single leading slash without a trailing slash', function () {
    expect(RedirectPatternMatcher::normalizePath('/old/'))->toBe('/old')
        ->and(RedirectPatternMatcher::normalizePath('old'))->toBe('/old')
        ->and(RedirectPatternMatcher::normalizePath('/'))->toBe('/');
});

it('strips the query string and host when normalizing', function () {
    expect(RedirectPatternMatcher::normalizePath('/old?ref=x'))->toBe('/old')
        ->and(RedirectPatternMatcher::normalizePath('https://example.com/old/'))->toBe('/old');
});
