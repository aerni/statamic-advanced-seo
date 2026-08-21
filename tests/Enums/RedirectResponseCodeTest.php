<?php

use Aerni\AdvancedSeo\Enums\RedirectResponseCode;

it('returns a non-empty label for each redirect type', function () {
    foreach (RedirectResponseCode::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});
