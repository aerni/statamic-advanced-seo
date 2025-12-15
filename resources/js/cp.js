import SiteIndex from './pages/site/Index.vue';
import CollectionsIndex from './pages/collections/Index.vue';
import TaxonomiesIndex from './pages/taxonomies/Index.vue';
import SeoDefaultsEdit from './pages/seo-defaults/Edit.vue';
import SourceFieldtype from './components/SourceFieldtype.vue'
import SocialImageFieldtype from './components/SocialImageFieldtype.vue'

Statamic.booting(() => {
    Statamic.$inertia.register('advanced-seo::Site/Index', SiteIndex)
    Statamic.$inertia.register('advanced-seo::Collections/Index', CollectionsIndex)
    Statamic.$inertia.register('advanced-seo::Taxonomies/Index', TaxonomiesIndex)
    Statamic.$inertia.register('advanced-seo::SeoDefaults/Edit', SeoDefaultsEdit)
    Statamic.$components.register('seo_source-fieldtype', SourceFieldtype)
    Statamic.$components.register('social_image-fieldtype', SocialImageFieldtype)
})
