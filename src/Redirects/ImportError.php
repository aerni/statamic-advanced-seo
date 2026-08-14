<?php

namespace Aerni\AdvancedSeo\Redirects;

use Illuminate\Contracts\Support\Arrayable;

class ImportError implements Arrayable
{
    public function __construct(
        public readonly int $row,
        public readonly string $source,
        public readonly string $message,
    ) {}

    /**
     * @return array{row: int, source: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'source' => $this->source,
            'message' => $this->message,
        ];
    }
}
