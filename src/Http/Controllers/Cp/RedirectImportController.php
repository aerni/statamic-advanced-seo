<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\RedirectImportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;

class RedirectImportController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        throw_unless(RedirectImportExport::enabled(), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        [$upload, $path] = $this->resolveUpload($request);

        try {
            $result = RedirectFacade::import($path);
        } finally {
            Storage::disk('local')->delete($upload);
        }

        return response()->json($result);
    }

    /**
     * Resolve and validate Statamic's client-provided temporary upload token.
     *
     * The canonical path must remain inside the temporary upload directory so
     * the import and cleanup cannot read or delete another local file.
     *
     * @return array{string, string}
     */
    protected function resolveUpload(Request $request): array
    {
        $validated = $request->validate([
            'file' => ['required', 'array', 'size:1'],
            'file.0' => ['required', 'string'],
        ]);

        $token = $validated['file'][0];
        $validToken = ! str_contains($token, "\0")
            && in_array(strtolower(pathinfo($token, PATHINFO_EXTENSION)), ['csv', 'json'], true);

        if (! $validToken) {
            throw ValidationException::withMessages([
                'file.0' => __('advanced-seo::validation.redirect_import_invalid_file'),
            ]);
        }

        $upload = "statamic/file-uploads/{$token}";
        $disk = Storage::disk('local');
        $directory = realpath($disk->path('statamic/file-uploads'));
        $path = realpath($disk->path($upload));

        $validPath = $directory !== false
            && $path !== false
            && is_file($path)
            && str_starts_with($path, $directory.DIRECTORY_SEPARATOR);

        if (! $validPath) {
            throw ValidationException::withMessages([
                'file.0' => __('advanced-seo::validation.redirect_import_invalid_file'),
            ]);
        }

        return [$upload, $path];
    }
}
