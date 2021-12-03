<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Illuminate\Routing\Controller;
use Statamic\Contracts\Entries\Entry;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Data;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\View\View;

class SocialImagesController extends Controller
{
    public function show(string $type, string $id): View
    {
        // Throw if the social images generator is disabled.
        throw_unless(config('advanced-seo.social_images.generator.enabled', false), new NotFoundHttpException);

        // Throw if the social image type is not supported.
        throw_unless($specs = SocialImage::types()->get($type), new NotFoundHttpException);

        // Throw if no data was found.
        throw_unless($data = Data::find($id), new NotFoundHttpException);

        // Throw if the data is not an entry or term.
        throw_unless($data instanceof Entry || $data instanceof LocalizedTerm, new NotFoundHttpException());

        return (new View)
            ->template("social_images/$type")
            ->layout('social_images/layout')
            ->with($data->merge($specs)->toAugmentedArray());
    }
}
