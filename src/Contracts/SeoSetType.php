<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SeoSetType
{
    public function type(): string;

    public function title(): string;

    public function route(): string;

    public function icon(): string;
}
