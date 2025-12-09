import SiteIndex from './pages/site/Index.vue';
import CollectionsIndex from './pages/collections/Index.vue';
import TaxonomiesIndex from './pages/taxonomies/Index.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('advanced-seo::Site/Index', SiteIndex);
    Statamic.$inertia.register('advanced-seo::Collections/Index', CollectionsIndex);
    Statamic.$inertia.register('advanced-seo::Taxonomies/Index', TaxonomiesIndex);
})
