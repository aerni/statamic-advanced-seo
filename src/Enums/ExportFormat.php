<?php

namespace Aerni\AdvancedSeo\Enums;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Json = 'json';

    public function contentType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv',
            self::Json => 'application/json',
        };
    }
}
