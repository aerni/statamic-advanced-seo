import DefaultsPublishForm from './components/DefaultsPublishForm'
import SeoMetaTitleFieldtype from './components/SeoMetaTitleFieldtype'

Statamic.booting(() => {
    Statamic.component('defaults-publish-form', DefaultsPublishForm)
    Statamic.component('seo_meta_title-fieldtype', SeoMetaTitleFieldtype)
})
