<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\RedirectImportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;

class RedirectImportController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        throw_unless(RedirectImportExport::enabled(), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        $request->validate(['file' => ['required', 'array', 'min:1']]);

        $upload = "statamic/file-uploads/{$request->input('file')[0]}";

        try {
            $result = RedirectFacade::import(Storage::disk('local')->path($upload));
        } finally {
            Storage::disk('local')->delete($upload);
        }

        return response()->json($result);
    }
}
