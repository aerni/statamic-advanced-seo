<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Web;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Registries\SocialImageThemeRegistry;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\View\View;

class SocialImagesController extends Controller
{
    public function __invoke(string $theme, string $template, string $id, string $site): Response
    {
        $theme = Str::replace('-', '_', $theme);
        $template = Str::replace('-', '_', $template);

        // Throw if the social images generator is disabled.
        throw_unless(config('advanced-seo.social_images.generator.enabled', false), new NotFoundHttpException);

        // Throw if no data was found.
        throw_unless($data = Data::find($id)?->in($site), new NotFoundHttpException);

        // Throw if the data is not an entry or term.
        throw_unless($data instanceof Entry || $data instanceof LocalizedTerm, new NotFoundHttpException);

        // Throw if we can't find a social image.
        throw_unless($socialImage = SocialImage::find($template), new NotFoundHttpException);

        // Get the view path for the template from the theme.
        $templatePath = (new SocialImageThemeRegistry)->find($theme)?->template($template);

        // Throw if no template was found for the given theme.
        throw_unless($templatePath, new NotFoundHttpException);

        // Prevent an infinite loop when an image is generated in the augment method of the SocialImageFieldtype.
        $data->set('seo_generate_social_images', false);

        Site::setCurrent($data->site()->handle());

        $view = (new View)
            ->template($templatePath)
            ->layout('social_images/layout')
            ->cascadeContent($data)
            ->with([
                'width' => $socialImage->width(),
                'height' => $socialImage->height(),
                'type' => $socialImage->type,
            ]);

        return response($view)->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
