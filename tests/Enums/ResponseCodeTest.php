<?php

use Aerni\AdvancedSeo\Enums\ResponseCode;

it('returns a non-empty label for each redirect type', function () {
    foreach (ResponseCode::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});
