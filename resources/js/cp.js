import Dashboard from './pages/Dashboard.vue';
import Index from './pages/Index.vue';
import Edit from './pages/Edit.vue';
import SeoFieldtype from './components/fieldtypes/SeoFieldtype.vue'
import SiteOriginsFieldtype from './components/fieldtypes/SiteOriginsFieldtype.vue'
import SearchPreviewFieldtype from './components/fieldtypes/SearchPreviewFieldtype.vue'
import SocialPreviewFieldtype from './components/fieldtypes/SocialPreviewFieldtype.vue'
import TokenInputFieldtype from './components/fieldtypes/TokenInputFieldtype.vue'
import { add } from './utils/normalizers.js'

Statamic.booting(() => {
    Statamic.$inertia.register('advanced-seo::Dashboard', Dashboard)
    Statamic.$inertia.register('advanced-seo::Site/Edit', Edit)
    Statamic.$inertia.register('advanced-seo::Collections/Index', Index)
    Statamic.$inertia.register('advanced-seo::Collections/Edit', Edit)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Index', Index)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Edit', Edit)
    Statamic.$components.register('seo-fieldtype', SeoFieldtype)
    Statamic.$components.register('site_origins-fieldtype', SiteOriginsFieldtype)
    Statamic.$components.register('search_preview-fieldtype', SearchPreviewFieldtype)
    Statamic.$components.register('social_preview-fieldtype', SocialPreviewFieldtype)
    Statamic.$components.register('token_input-fieldtype', TokenInputFieldtype)
    Statamic.$advancedSeo = { normalizers: { add } }
})
