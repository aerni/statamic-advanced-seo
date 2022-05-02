<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Statamic\View\View;
use Statamic\Facades\Data;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Facades\Statamic\CP\LivePreview;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Exceptions\NotFoundHttpException;

class SocialImagesController extends Controller
{
    public function show(string $type, string $theme, string $id, Request $request): Response
    {
        // Throw if the social images generator is disabled.
        throw_unless(config('advanced-seo.social_images.generator.enabled', false), new NotFoundHttpException);

        // Throw if no data was found.
        throw_unless($data = $this->getData($id, $request), new NotFoundHttpException);

        // Throw if the data is not an entry or term.
        throw_unless($data instanceof Entry || $data instanceof LocalizedTerm, new NotFoundHttpException());

        // Throw if the social image type is not supported.
        throw_unless($model = SocialImage::findModel(Str::replace('-', '_', $type)), new NotFoundHttpException);

        $view = (new View)
            ->template($model['templates']->get(Str::replace('-', '_', $theme)))
            ->layout($model['layout'])
            ->with($data->merge($model)->toAugmentedArray());

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
