Statamic.booted(() => {
    Statamic.$store.dispatch("publish/advancedSeo/fetchConditions")
})

Statamic.$conditions.add('showSitemapFields', ({ store }) => {
    return store.state.publish.advancedSeo.conditions?.showSitemapFields
});

Statamic.$conditions.add('showSocialImagesGeneratorFields', ({ store }) => {
    return store.state.publish.advancedSeo.conditions?.showSocialImagesGeneratorFields
});
