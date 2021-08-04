<?php

namespace Aerni\AdvancedSeo\Contracts;

interface Globals
{
    public function handle(): string;

    public function title(): string;

    public function blueprint(): array;
}
