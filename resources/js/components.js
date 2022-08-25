import DefaultsPublishForm from './components/DefaultsPublishForm'
import SocialImageFieldtype from './components/SocialImageFieldtype'
import SourceFieldtype from './components/SourceFieldtype'

Statamic.booting(() => {
    Statamic.component('defaults-publish-form', DefaultsPublishForm)
    Statamic.component('social_image-fieldtype', SocialImageFieldtype)
    Statamic.component('seo_source-fieldtype', SourceFieldtype)
})
