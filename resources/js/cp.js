import DefaultsPublishForm from './components/DefaultsPublishForm'
import SocialImagesPreviewFieldtype from './components/SocialImagesPreviewFieldtype'
import SourceFieldtype from './components/SourceFieldtype'

Statamic.booting(() => {
    Statamic.component('defaults-publish-form', DefaultsPublishForm)
    Statamic.component(
        'social_images_preview-fieldtype',
        SocialImagesPreviewFieldtype
    )
    Statamic.component('seo_source-fieldtype', SourceFieldtype)
})
