<template>
    <div>
        <text-input
            :placeholder="placeholder"
            :value="value"
            :append="siteName"
            :limit="70"
            @input="update" />
    </div>
</template>

<script>
    export default {
        name: 'seo-meta-title-fieldtype',

        mixins: [Fieldtype],

        inject: ['storeName'],

        computed: {
            placeholder() {
                return this.siteDefaults.seo_title || this.pageTitle || ''
            },

            siteName() {
                return `${this.siteDefaults.title_separator} ${this.siteDefaults.site_name}`
            },

            siteDefaults() {
                return this.meta[this.baseStore.site] || this.meta[this.statamicStore.selectedSite]
            },

            baseStore() {
                return this.$store.state.publish[this.storeName]
            },

            statamicStore() {
                return this.$store.state.statamic.config
            },

            pageTitle() {
                return this.baseStore.values.title
            },
        },
    }
</script>
