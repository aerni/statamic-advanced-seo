<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Facades\Statamic\CP\LivePreview;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Statamic\Contracts\Entries\Entry;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Data;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\View\View;

class SocialImagesController extends Controller
{
    public function show(string $type, string $id, Request $request): Response
    {
        // Throw if the social images generator is disabled.
        throw_unless(config('advanced-seo.social_images.generator.enabled', false), new NotFoundHttpException);

        // Throw if no data was found.
        throw_unless($data = $this->getData($id, $request), new NotFoundHttpException);

        // Throw if the data is not an entry or term.
        throw_unless($data instanceof Entry || $data instanceof LocalizedTerm, new NotFoundHttpException());

        // Throw if the social image type is not supported.
        throw_unless($specs = SocialImage::specs($type, $data), new NotFoundHttpException);

        $template = $specs['templates']->get($request->get('theme')) // Get the template based on the theme in the request.
            ?? $specs['templates']->get('default') // If no theme is set, use the default theme.
            ?? $specs['templates']->first(); // If the default doesn't exist either, fall back to the first theme.

        $view = (new View)
            ->template($template)
            ->layout($specs['layout'])
            ->with($data->merge($specs)->toAugmentedArray());

        return response($view)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    protected function getData(string $id, Request $request): ?Entry
    {
        if ($request->statamicToken()) {
            return LivePreview::item($request->statamicToken());
        }

        return Data::find($id);
    }
}
