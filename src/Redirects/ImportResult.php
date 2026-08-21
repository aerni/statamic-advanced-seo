<?php

namespace Aerni\AdvancedSeo\Redirects;

use Illuminate\Contracts\Support\Arrayable;

class ImportResult implements Arrayable
{
    /**
     * @param  array<int, ImportError>  $errors
     */
    public function __construct(
        public readonly int $imported,
        public readonly array $errors,
    ) {}

    public function successful(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array{imported: int, errors: array<int, array{row: int, source: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'errors' => array_map(fn (ImportError $error) => $error->toArray(), $this->errors),
        ];
    }
}
