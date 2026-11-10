<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\SocialImages\SocialImageView;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Data;
use Statamic\Taxonomies\LocalizedTerm;

class SocialImagesController extends Controller
{
    public function __invoke(string $theme, string $template, string $id, string $site): SocialImageView
    {
        $theme = Str::replace('-', '_', $theme);
        $template = Str::replace('-', '_', $template);

        // Throw if the social images generator is disabled.
        throw_unless(SocialImagesGenerator::enabled(), new NotFoundHttpException);

        // Throw if no data was found.
        throw_unless($data = Data::find($id)?->in($site), new NotFoundHttpException);

        // Throw if the data is not an entry or term.
        throw_unless($data instanceof Entry || $data instanceof LocalizedTerm, new NotFoundHttpException);

        // Throw if we can't find a social image.
        throw_unless($socialImage = SocialImage::find($template), new NotFoundHttpException);

        return new SocialImageView($socialImage, $data, $theme);
    }
}
