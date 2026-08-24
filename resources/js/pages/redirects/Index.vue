<script setup>
import { Head, router, toggleArchitecturalBackground, useArchitecturalBackground } from '@statamic/cms/inertia';
import { Icon, EmptyStateMenu, EmptyStateItem } from '@statamic/cms/ui';
import Listing from '../../components/redirects/Listing.vue';
import ImportRedirectsModal from '../../components/redirects/ImportRedirectsModal.vue';
import { ref, useTemplateRef } from 'vue';

const props = defineProps({
    title: String,
    createUrl: String,
    listingUrl: String,
    actionUrl: String,
    canImportExport: Boolean,
    exportCsvUrl: String,
    exportJsonUrl: String,
    importUrl: String,
    filters: Array,
    hasRedirects: Boolean,
});

const showImportModal = ref(false);
const listing = useTemplateRef('listing');

if (!props.hasRedirects) {
    useArchitecturalBackground();
}

function onImported() {
    listing.value?.refresh();
    router.reload({
        only: ['hasRedirects'],
        onSuccess: () => toggleArchitecturalBackground(false),
    });
}
</script>

<template>
    <Head :title="title" />

    <Listing
        v-if="hasRedirects"
        ref="listing"
        :title="title"
        :listing-url="listingUrl"
        :action-url="actionUrl"
        :filters="filters"
        :can-import-export="canImportExport"
        :create-url="createUrl"
        :export-csv-url="exportCsvUrl"
        :export-json-url="exportJsonUrl"
        @import="showImportModal = true"
    />

    <template v-else>
        <header class="py-8 pt-16 text-center">
            <h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
                <Icon name="moved" class="text-gray-500 size-5.5" />
                {{ __('advanced-seo::messages.redirects') }}
            </h1>
        </header>

        <EmptyStateMenu>
            <EmptyStateItem
                :href="createUrl"
                icon="moved"
                :heading="__('advanced-seo::messages.redirect_create_title')"
                :description="__('advanced-seo::messages.redirect_create_description')"
            />
            <EmptyStateItem
                v-if="canImportExport"
                icon="upload"
                :heading="__('advanced-seo::messages.redirect_import_title')"
                :description="__('advanced-seo::messages.redirect_import_description')"
                @click="showImportModal = true"
            />
        </EmptyStateMenu>
    </template>

    <ImportRedirectsModal
        v-if="showImportModal"
        :import-url="importUrl"
        @closed="showImportModal = false"
        @imported="onImported"
    />
</template>
