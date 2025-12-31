<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SeoSet
{
    public function id(): string;

    public function type(): string;

    public function handle(): string;

    public function title(): string;

    public function icon(): string;
}
