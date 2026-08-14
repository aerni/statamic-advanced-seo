<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\ExportFormat;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\RedirectImportExport;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpFoundation\Response;

class RedirectExportController extends CpController
{
    public function __invoke(string $format): Response
    {
        throw_unless(RedirectImportExport::enabled(), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        $format = ExportFormat::from($format);

        return response()->streamDownload(
            fn () => print RedirectFacade::export($format),
            'redirects-'.now()->format('Y-m-d-His').".{$format->value}",
            ['Content-Type' => $format->contentType()],
        );
    }
}
