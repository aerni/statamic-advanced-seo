<?php

use Aerni\AdvancedSeo\Enums\RedirectErrorStatus;
use Aerni\AdvancedSeo\Redirects\Redirect;

it('is unhandled when no redirect covers the error', function () {
    expect(RedirectErrorStatus::for(null))->toBe(RedirectErrorStatus::Unhandled);
});

it('is enabled when covered by an enabled redirect', function () {
    $redirect = (new Redirect)->source('/old')->destination('/new')->enabled(true);

    expect(RedirectErrorStatus::for($redirect))->toBe(RedirectErrorStatus::Enabled);
});

it('is disabled when covered by a disabled redirect', function () {
    $redirect = (new Redirect)->source('/old')->destination('/new')->enabled(false);

    expect(RedirectErrorStatus::for($redirect))->toBe(RedirectErrorStatus::Disabled);
});
