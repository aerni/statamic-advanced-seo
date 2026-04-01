<?php

use Aerni\AdvancedSeo\Features\GraphQL;

it('is disabled on the free edition', function () {
    useFreeEdition();

    config([
        'statamic.graphql.enabled' => true,
        'advanced-seo.graphql' => true,
    ]);

    expect(GraphQL::enabled())->toBeFalse();
});

it('is disabled by default', function () {
    expect(GraphQL::enabled())->toBeFalse();
});

it('is disabled when statamic graphql is disabled', function () {
    config([
        'statamic.graphql.enabled' => false,
        'advanced-seo.graphql' => true,
    ]);

    expect(GraphQL::enabled())->toBeFalse();
});

it('is disabled when advanced seo graphql is disabled', function () {
    config([
        'statamic.graphql.enabled' => true,
        'advanced-seo.graphql' => false,
    ]);

    expect(GraphQL::enabled())->toBeFalse();
});

it('is enabled when both graphql configs are enabled', function () {
    config([
        'statamic.graphql.enabled' => true,
        'advanced-seo.graphql' => true,
    ]);

    expect(GraphQL::enabled())->toBeTrue();
});
