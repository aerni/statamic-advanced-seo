<?php

use Aerni\AdvancedSeo\Rules\ValidRedirectSource;
use Illuminate\Support\Facades\Validator;

function passesSource(string $source): bool
{
    return Validator::make(['source' => $source], ['source' => [new ValidRedirectSource]])->passes();
}

it('accepts a leading-slash path', fn () => expect(passesSource('/old'))->toBeTrue());
it('accepts a wildcard path', fn () => expect(passesSource('/blog/*'))->toBeTrue());
it('accepts a compilable regex', fn () => expect(passesSource('#^/p/(\d+)$#'))->toBeTrue());
it('rejects a path without a leading slash', fn () => expect(passesSource('old'))->toBeFalse());
it('rejects a malformed regex', fn () => expect(passesSource('#^/p/(\d+$#'))->toBeFalse());
