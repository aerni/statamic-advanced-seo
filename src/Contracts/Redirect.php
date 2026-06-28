<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Enums\MatchType;
use Aerni\AdvancedSeo\Enums\RedirectType;

interface Redirect
{
    public function id(?string $id = null): string|self;

    public function source(?string $source = null): string|self|null;

    public function destination(?string $destination = null): string|self|null;

    public function type(?RedirectType $type = null): RedirectType|self;

    public function matchType(): MatchType;

    public function site(?string $site = null): string|self;

    public function enabled(?bool $enabled = null): bool|self;

    public function description(?string $description = null): string|self|null;

    public function editUrl(): string;

    public function deleteUrl(): string;

    public function path(): string;

    public function fileData(): array;

    public function save(): self;

    public function delete(): bool;
}
