<?php

namespace Aerni\AdvancedSeo\Tokens;

use Aerni\AdvancedSeo\Registries\TokenRegistry;
use Illuminate\Support\Traits\ForwardsCalls;
use Statamic\Fields\Field;
use Statamic\Fields\Value;

/**
 * @mixin TokenRegistry
 */
class TokenService
{
    use ForwardsCalls;

    public function __construct(
        protected TokenRegistry $registry,
        protected TokenParser $parser,
    ) {}

    public function for(mixed $parent): Tokens
    {
        return new Tokens($parent);
    }

    public function normalize(Value $value): ?string
    {
        return $this->registry->normalizers()->get($value->fieldtype()?->handle())?->normalize($value);
    }

    public function parse(?string $data, Field $field): ?string
    {
        return $this->parser->parse($data, $field);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->registry, $method, $parameters);
    }
}
