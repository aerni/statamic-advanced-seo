<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\SimpleExcel\SimpleExcelReader;
use Statamic\Facades\Site;

class RedirectImporter
{
    public function __construct(protected RedirectDestinationEntries $destinationEntries) {}

    /**
     * Import redirects from a CSV or JSON file, choosing the parser by extension.
     * A structurally invalid file throws a ValidationException; per-row failures
     * come back in the result. Nothing is saved unless every row is valid.
     *
     * @throws ValidationException
     */
    public function import(string $path): ImportResult
    {
        $rows = $this->parse($path);

        $this->destinationEntries->preload($this->destinationEntryIds($rows));

        [$redirects, $errors] = $this->prepareRows($rows)
            ->partition(fn ($result) => $result instanceof Redirect);

        // All or nothing: if any row is invalid, import none of them.
        if ($errors->isNotEmpty()) {
            return new ImportResult(imported: 0, errors: $errors->values()->all());
        }

        $redirects->each->save();

        return new ImportResult(imported: $redirects->count(), errors: []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, string>
     */
    protected function destinationEntryIds(array $rows): Collection
    {
        return collect($rows)
            ->reject(fn (array $row) => (int) ($row['response_code'] ?? RedirectResponseCode::Permanent->value) === RedirectResponseCode::Gone->value)
            ->pluck('destination')
            ->filter(fn ($destination) => is_string($destination))
            ->map(fn (string $destination) => trim($destination))
            ->filter(fn (string $destination) => Str::startsWith($destination, 'entry::'))
            ->map(fn (string $destination) => Str::after($destination, 'entry::'))
            ->unique()
            ->values();
    }

    /**
     * Read the uploaded file into a list of rows, choosing the parser by extension.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parse(string $path): array
    {
        return Str::endsWith(Str::lower($path), '.csv')
            ? $this->parseCsv($path)
            : $this->parseJson($path);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    protected function parseCsv(string $path): array
    {
        $reader = SimpleExcelReader::create($path);

        $headers = collect($reader->getHeaders())
            ->map(fn (string $header): string => Str::of($header)->lower()->snake());

        $missingHeaders = collect(['source', 'destination'])
            ->when(Site::multiEnabled(), fn (Collection $collection) => $collection->push('site'))
            ->reject(fn (string $key) => $headers->contains($key));

        throw_if(
            $missingHeaders->isNotEmpty(),
            ValidationException::withMessages([
                'file' => trans_choice('advanced-seo::messages.redirect_import_missing_columns', $missingHeaders->count(), [
                    'columns' => $missingHeaders->implode(', '),
                ]),
            ]),
        );

        return $reader->useHeaders($headers->all())->getRows()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseJson(string $path): array
    {
        $rows = json_decode((string) file_get_contents($path), true);

        $isListOfObjects = is_array($rows)
            && array_is_list($rows)
            && collect($rows)->every(fn ($row) => is_array($row));

        throw_unless(
            $isListOfObjects,
            ValidationException::withMessages(['file' => __('advanced-seo::messages.redirect_import_invalid_json')]),
        );

        return $rows;
    }

    /**
     * Validate every row without saving, so a single invalid row aborts the whole
     * import. Tracks duplicate source+site within the file, which would otherwise
     * create duplicates since nothing is persisted during validation.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Redirect|ImportError>
     */
    protected function prepareRows(array $rows): Collection
    {
        $seen = [];

        return collect($rows)->map(function (array $row, int $index) use (&$seen): Redirect|ImportError {
            try {
                $redirect = $this->prepareRow($row);

                $key = $redirect->site().' '.$redirect->source();

                throw_if(
                    in_array($key, $seen, true),
                    ValidationException::withMessages(['source' => __('advanced-seo::messages.redirect_import_duplicate')]),
                );

                $seen[] = $key;

                return $redirect;
            } catch (ValidationException $e) {
                return new ImportError(
                    row: $index + 1,
                    source: trim((string) ($row['source'] ?? '')),
                    message: (string) collect($e->errors())->flatten()->first(),
                );
            }
        });
    }

    /**
     * Validate one row and return the staged (unsaved) redirect to persist once the
     * whole file is valid. Throws if the row is invalid.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    protected function prepareRow(array $row): Redirect
    {
        $source = trim((string) ($row['source'] ?? ''));
        $site = $this->resolveSite($row);
        $normalizedSource = RedirectFacade::make()->source($source)->source();

        $existing = RedirectFacade::query()
            ->where('source', $normalizedSource)
            ->where('site', $site)
            ->first();

        // Import fully overwrites the redirect, so absent columns reset to the field's default rather than keeping the existing value.
        $responseCode = $this->int($row, 'response_code', RedirectResponseCode::Permanent->value);

        throw_if(
            RedirectResponseCode::tryFrom($responseCode) === null,
            ValidationException::withMessages(['response_code' => __('advanced-seo::messages.redirect_import_invalid_response_code', ['code' => $responseCode])]),
        );

        $values = [
            'source' => $source,
            'response_code' => $responseCode,
            'preserve_query_string' => $this->bool($row, 'preserve_query_string', true),
            'site' => $site,
        ];

        // Gone redirects have no destination; omit the key so the blueprint's `sometimes` rule skips it, matching the publish form.
        if ($responseCode !== RedirectResponseCode::Gone->value) {
            $values['destination'] = $this->string($row, 'destination');
        }

        $fields = RedirectBlueprint::definition()->fields()->addValues($values);

        $fields->validator()->withReplacements(['id' => $existing?->id(), 'site' => $site])->validate();
        $this->validateDestinationExists(Arr::get($values, 'destination'));

        $values = $fields->process()->values()->all();

        return ($existing ?? RedirectFacade::make())
            ->source(Arr::get($values, 'source'))
            ->destination(Arr::get($values, 'destination'))
            ->responseCode(RedirectResponseCode::from(Arr::get($values, 'response_code') ?? RedirectResponseCode::Permanent->value))
            ->preserveQueryString(Arr::get($values, 'preserve_query_string'))
            ->enabled($this->bool($row, 'enabled', true))
            ->description($this->string($row, 'description'))
            ->site($site)
            ->origin(RedirectOrigin::Import);
    }

    protected function validateDestinationExists(?string $destination): void
    {
        if (! Str::startsWith($destination, 'entry::')) {
            return;
        }

        throw_unless(
            $this->destinationEntries->find(Str::after($destination, 'entry::')),
            ValidationException::withMessages(['destination' => __('advanced-seo::validation.redirect_destination_missing')]),
        );
    }

    /**
     * Resolve the target site, or throw. A blank site falls back to the selected
     * site on single-site, but is required on multi-site so redirects are never
     * imported into the wrong site; an unknown/unauthorized handle is rejected.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    protected function resolveSite(array $row): string
    {
        if (! $this->present($row, 'site')) {
            if (Site::multiEnabled()) {
                throw ValidationException::withMessages(['site' => __('advanced-seo::messages.redirect_import_missing_site')]);
            }

            return Site::selected()->handle();
        }

        $handle = trim((string) $row['site']);

        if (! Site::authorized()->map->handle()->contains($handle)) {
            throw ValidationException::withMessages(['site' => __('advanced-seo::messages.redirect_import_invalid_site', ['site' => $handle])]);
        }

        return $handle;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function int(array $row, string $key, int $default): int
    {
        return $this->present($row, $key) ? (int) $row[$key] : $default;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function bool(array $row, string $key, bool $default): bool
    {
        return $this->present($row, $key) ? filter_var($row[$key], FILTER_VALIDATE_BOOLEAN) : $default;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function string(array $row, string $key, ?string $default = null): ?string
    {
        return $this->present($row, $key) ? trim((string) $row[$key]) : $default;
    }

    /**
     * Whether the row carries a usable, non-blank value for the key.
     *
     * @param  array<string, mixed>  $row
     */
    protected function present(array $row, string $key): bool
    {
        return array_key_exists($key, $row)
            && $row[$key] !== null
            && ! (is_string($row[$key]) && trim($row[$key]) === '');
    }
}
