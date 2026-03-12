<?php

namespace Aerni\AdvancedSeo\Tokens;

use Aerni\AdvancedSeo\Registries\TokenRegistry;
use Statamic\Fields\Field;
use Statamic\Fields\Value;

class TokenService
{
    public function __construct(
        protected TokenRegistry $registry,
        protected TokenParser $parser,
    ) {}

    public function for(mixed $parent): Tokens
    {
        return new Tokens($parent);
    }

    public function registry(): TokenRegistry
    {
        return $this->registry;
    }

    public function normalize(Value $value): ?string
    {
        return $this->registry->normalizers()->get($value->fieldtype()?->handle())?->normalize($value);
    }

    public function parse(?string $data, Field $field): ?string
    {
        return $this->parser->parse($data, $field);
    }
}
