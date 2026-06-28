<?php

use Aerni\AdvancedSeo\Enums\RedirectType;

it('returns a non-empty label for each redirect type', function () {
    foreach (RedirectType::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});
