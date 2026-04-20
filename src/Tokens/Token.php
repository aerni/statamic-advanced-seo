<?php

namespace Aerni\AdvancedSeo\Tokens;

use Illuminate\Contracts\Support\Arrayable;

interface Token extends Arrayable
{
    public function handle(): string;

    public function display(): string;

    public function group(): string;

    public function toArray(): array;
}
