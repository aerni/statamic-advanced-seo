import DefaultsPublishForm from './components/DefaultsPublishForm.vue'
import SocialImageFieldtype from './components/SocialImageFieldtype.vue'
import SourceFieldtype from './components/SourceFieldtype.vue'

Statamic.booting(() => {
    Statamic.component('defaults-publish-form', DefaultsPublishForm)
    Statamic.component('social_image-fieldtype', SocialImageFieldtype)
    Statamic.component('seo_source-fieldtype', SourceFieldtype)
})
