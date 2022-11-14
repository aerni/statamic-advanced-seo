// Fetch the conditions after Statamic has booted.
Statamic.booted(() => {
    Statamic.$store.dispatch("publish/advancedSeo/fetchConditions")
})

// Fetch the conditions when the user changes the site in the sidebar.
Statamic.$store.watch(state => state.publish?.base?.site, () => {
    Statamic.$store.dispatch("publish/advancedSeo/fetchConditions")
})

// Add the showSitemapFields condition.
Statamic.$conditions.add('showSitemapFields', ({ store }) => {
    return store.state.publish.advancedSeo.conditions?.showSitemapFields
});

// Add the showSocialImagesGeneratorFields condition.
Statamic.$conditions.add('showSocialImagesGeneratorFields', ({ store }) => {
    return store.state.publish.advancedSeo.conditions?.showSocialImagesGeneratorFields
});
