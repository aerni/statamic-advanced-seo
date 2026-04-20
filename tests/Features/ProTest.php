<?php

use Aerni\AdvancedSeo\Features\Pro;

it('is enabled on the pro edition', function () {
    expect(Pro::enabled())->toBeTrue();
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    expect(Pro::enabled())->toBeFalse();
});
