Statamic.booted(() => {
    if (id = Statamic.$store.state.publish?.base?.values?.id) {
        Statamic.$store.dispatch("publish/advancedSeo/fetchConditions", { id: id })
    }
})

Statamic.$conditions.add('showSitemapSettings', ({ store }) => {
    return store.state.publish.advancedSeo.conditions.showSitemapSettings
});

Statamic.$conditions.add('showSocialImagesGenerator', ({ store }) => {
    return store.state.publish.advancedSeo.conditions.showSocialImagesGenerator
});
