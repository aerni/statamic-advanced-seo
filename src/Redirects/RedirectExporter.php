<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder;
use Aerni\AdvancedSeo\Enums\RedirectExportFormat;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Statamic\Facades\Site;

class RedirectExporter
{
    /**
     * Export the redirects the current user is authorized to see as a CSV or JSON string.
     */
    public function export(RedirectExportFormat $format = RedirectExportFormat::Csv, ?RedirectQueryBuilder $query = null): string
    {
        return match ($format) {
            RedirectExportFormat::Csv => $this->toCsv($query),
            RedirectExportFormat::Json => $this->toJson($query),
        };
    }

    protected function toCsv(?RedirectQueryBuilder $query): string
    {
        $path = tempnam(sys_get_temp_dir(), 'redirects-export-').'.csv';

        $writer = SimpleExcelWriter::createWithoutBom($path);

        $this->rows($query)
            ->map(fn (array $row) => array_map(
                fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value,
                $row,
            ))
            ->each(fn (array $row) => $writer->addRow($row));

        $writer->close();

        $contents = (string) file_get_contents($path);

        @unlink($path);

        return $contents;
    }

    protected function toJson(?RedirectQueryBuilder $query): string
    {
        return $this->rows($query)->toPrettyJson(JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return Collection<int, array<string, string|int|bool|null>>
     */
    protected function rows(?RedirectQueryBuilder $query): Collection
    {
        return ($query ?? RedirectFacade::query())
            ->whereIn('site', Site::authorized()->map->handle()->all())
            ->get()
            ->map(function (Redirect $redirect): array {
                $isGone = $redirect->responseCode() === RedirectResponseCode::Gone;

                return [
                    'source' => $redirect->source(),
                    'destination' => $isGone ? null : $redirect->destination(),
                    'response_code' => $redirect->responseCode()->value,
                    'preserve_query_string' => $isGone ? null : $redirect->preserveQueryString(),
                    'site' => $redirect->site(),
                    'enabled' => $redirect->enabled(),
                    'description' => $redirect->description(),
                ];
            });
    }
}
