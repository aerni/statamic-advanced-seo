import DefaultsPublishForm from './components/DefaultsPublishForm'
import SocialImagesPreviewFieldtype from './components/SocialImagesPreviewFieldtype'

Statamic.booting(() => {
    Statamic.component('defaults-publish-form', DefaultsPublishForm)
    Statamic.component(
        'social_images_preview-fieldtype',
        SocialImagesPreviewFieldtype
    )
})
