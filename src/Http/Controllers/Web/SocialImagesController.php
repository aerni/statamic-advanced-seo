<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Facades\Statamic\CP\LivePreview;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Data;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\View\View;

class SocialImagesController extends Controller
{
    public function show(string $theme, string $type, string $id): Response
    {
        // Throw if the social images generator is disabled.
        throw_unless(config('advanced-seo.social_images.generator.enabled', false), new NotFoundHttpException);

        // Throw if no data was found.
        throw_unless($data = $this->getData($id), new NotFoundHttpException);

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

    protected function getData(string $id): ?Entry
    {
        if (request()->statamicToken()) {
            return LivePreview::item(request()->statamicToken());
        }

        return Data::find($id);
    }
}
