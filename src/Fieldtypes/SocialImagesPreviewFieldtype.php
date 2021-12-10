<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Fieldtype;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Aerni\AdvancedSeo\Facades\SocialImage;

class SocialImagesPreviewFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $entry = $this->field->parent();
        $type = $this->config()['image_type'];

        if (! $entry instanceof Entry && ! $entry instanceof LocalizedTerm) {
            return ['title' => $this->field->display()];
        }

        return [
            'image' => $entry->augmentedValue(SocialImage::specs($type, $entry)['field'])?->value()?->absoluteUrl(),
            'title' => $this->field->display(),
        ];
    }
}
