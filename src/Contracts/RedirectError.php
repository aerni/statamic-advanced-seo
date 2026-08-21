<?php

namespace Aerni\AdvancedSeo\Contracts;

interface RedirectError
{
    public function id(?string $id = null): string|self;

    public function url(?string $url = null): string|self|null;

    public function site(?string $site = null): string|self;

    public function count(?int $count = null): int|self;

    public function firstSeenAt(?int $firstSeenAt = null): int|self|null;

    public function lastSeenAt(?int $lastSeenAt = null): int|self|null;

    public function firstSeenAtIso(): ?string;

    public function lastSeenAtIso(): ?string;

    public function path(): string;

    public function fileData(): array;

    public function save(): self;

    public function delete(): bool;
}
