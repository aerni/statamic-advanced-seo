<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\SocialImages\ThemeCollection;
use Statamic\Facades\Blink;
use Statamic\Fieldtypes\Select;

class SocialImagesThemeFieldtype extends Select
{
    protected static $handle = 'social_images_theme';

    protected $component = 'select';

    protected $selectable = false;

    public function preProcess($value)
    {
        return parent::preProcess($this->validatedValue($value));
    }

    public function augment($value)
    {
        return parent::augment($this->validatedValue($value));
    }

    protected function getOptions(): array
    {
        return $this->allowedThemes()->map(fn ($theme) => [
            'value' => $theme->handle,
            'label' => $theme->title,
        ])->values()->all();
    }

    /**
     * Ensure the value is still a valid option. If a theme was removed
     * while entries still reference it, fall back to the default theme.
     */
    protected function validatedValue(mixed $value): mixed
    {
        $themes = $this->allowedThemes();

        return $themes->firstWhere('handle', $value)?->handle
            ?? $themes->default()?->handle;
    }

    protected function allowedThemes(): ThemeCollection
    {
        return Blink::once("advanced-seo.allowed-themes.{$this->field()->parent()->id()}", function () {
            $context = Context::from($this->field()->parent());

            return SocialImage::themes()->allowedFor($context->seoSet());
        });
    }
}
