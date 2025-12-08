import DefaultsPublishForm from './components/DefaultsPublishForm.vue'
import SocialImageFieldtype from './components/SocialImageFieldtype.vue'
import SourceFieldtype from './components/SourceFieldtype.vue'

Statamic.booting(() => {
    Statamic.$components.register('defaults-publish-form', DefaultsPublishForm)
    Statamic.$components.register('social_image-fieldtype', SocialImageFieldtype)
    Statamic.$components.register('seo_source-fieldtype', SourceFieldtype)
})
