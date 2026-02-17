import Dashboard from './pages/Dashboard.vue';
import SiteIndex from './pages/site/Index.vue';
import SiteEdit from './pages/shared/Edit.vue';
import CollectionsIndex from './pages/shared/Index.vue';
import CollectionsEdit from './pages/shared/Edit.vue';
import TaxonomiesIndex from './pages/shared/Index.vue';
import TaxonomiesEdit from './pages/shared/Edit.vue';
import SeoFieldtype from './components/fieldtypes/SeoFieldtype.vue'
import SiteOriginsFieldtype from './components/fieldtypes/SiteOriginsFieldtype.vue'
import SearchPreviewFieldtype from './components/fieldtypes/SearchPreviewFieldtype.vue'
import SocialPreviewFieldtype from './components/fieldtypes/SocialPreviewFieldtype.vue'

Statamic.booting(() => {
    Statamic.$inertia.register('advanced-seo::Dashboard', Dashboard)
    Statamic.$inertia.register('advanced-seo::Site/Index', SiteIndex)
    Statamic.$inertia.register('advanced-seo::Site/Edit', SiteEdit)
    Statamic.$inertia.register('advanced-seo::Collections/Index', CollectionsIndex)
    Statamic.$inertia.register('advanced-seo::Collections/Edit', CollectionsEdit)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Index', TaxonomiesIndex)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Edit', TaxonomiesEdit)
    Statamic.$components.register('seo-fieldtype', SeoFieldtype)
    Statamic.$components.register('site_origins-fieldtype', SiteOriginsFieldtype)
    Statamic.$components.register('search_preview-fieldtype', SearchPreviewFieldtype)
    Statamic.$components.register('social_preview-fieldtype', SocialPreviewFieldtype)
})
