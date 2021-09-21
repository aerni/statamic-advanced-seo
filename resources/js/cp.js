import SeoMetaTitleFieldtype from './components/SeoMetaTitleFieldtype'

Statamic.booting(() => {
    Statamic.component('seo_meta_title-fieldtype', SeoMetaTitleFieldtype)
})
