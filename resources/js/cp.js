import Dashboard from './pages/Dashboard.vue';
import SiteIndex from './pages/site/Index.vue';
import SiteEdit from './pages/shared/Edit.vue';
import SiteConfig from './pages/shared/Config.vue';
import CollectionsIndex from './pages/shared/Index.vue';
import CollectionsEdit from './pages/shared/Edit.vue';
import CollectionsConfig from './pages/shared/Config.vue';
import TaxonomiesIndex from './pages/shared/Index.vue';
import TaxonomiesEdit from './pages/shared/Edit.vue';
import TaxonomiesConfig from './pages/shared/Config.vue';
import SourceFieldtype from './components/SourceFieldtype.vue'
import SocialImageFieldtype from './components/SocialImageFieldtype.vue'
import OriginFieldtype from './components/OriginFieldtype.vue'

Statamic.booting(() => {
    Statamic.$inertia.register('advanced-seo::Dashboard', Dashboard)
    Statamic.$inertia.register('advanced-seo::Site/Index', SiteIndex)
    Statamic.$inertia.register('advanced-seo::Site/Edit', SiteEdit)
    Statamic.$inertia.register('advanced-seo::Site/Config', SiteConfig)
    Statamic.$inertia.register('advanced-seo::Collections/Index', CollectionsIndex)
    Statamic.$inertia.register('advanced-seo::Collections/Edit', CollectionsEdit)
    Statamic.$inertia.register('advanced-seo::Collections/Config', CollectionsConfig)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Index', TaxonomiesIndex)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Edit', TaxonomiesEdit)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Config', TaxonomiesConfig)
    Statamic.$components.register('seo_source-fieldtype', SourceFieldtype)
    Statamic.$components.register('social_image-fieldtype', SocialImageFieldtype)
    Statamic.$components.register('origin-fieldtype', OriginFieldtype)
})
