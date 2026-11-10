<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Aerni\AdvancedSeo\Facades\SocialImage as SocialImageFacade;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\View\View;

/**
 * Render the social image in this process.
 *
 * The preview route looks the entry up again over HTTP. That lookup can
 * 404, which fails the save and the screenshot.
 */
class SocialImageView implements Responsable
{
    public function __construct(
        protected SocialImage $socialImage,
        protected Entry|Term $content,
        protected string $theme,
    ) {}

    public function render(): string
    {
        $site = Site::current();

        Site::setCurrent($this->content->site()->handle());

        try {
            return $this->view()->render();
        } finally {
            Site::setCurrent($site->handle());
        }
    }

    public function toResponse($request)
    {
        return response(
            content: $this->render(),
            headers: [
                'X-Robots-Tag' => 'noindex, nofollow',
            ],
        );
    }

    protected function view(): View
    {
        $content = clone $this->content;
        $template = Str::replace('-', '_', $this->socialImage->type);
        $templatePath = SocialImageFacade::themes()->find($this->theme)?->template($template);

        throw_unless($templatePath, new NotFoundHttpException);

        // Prevent an infinite loop when an image is generated in the augment method of the SocialImageFieldtype.
        $content->set('seo_generate_social_images', false);

        return (new View)
            ->template($templatePath)
            ->layout('social_images/layout')
            ->cascadeContent($content)
            ->with([
                'width' => $this->socialImage->width(),
                'height' => $this->socialImage->height(),
                'type' => $this->socialImage->type,
            ]);
    }
}
