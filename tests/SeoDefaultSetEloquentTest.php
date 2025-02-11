<?php

use Facades\Statamic\Console\Processes\Composer;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository;

pest()->use(Aerni\AdvancedSeo\Tests\UseEloquentDriver::class);

it('test', function () {
    dd(app(SeoDefaultsRepository::class));
});
