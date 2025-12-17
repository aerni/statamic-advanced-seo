<script setup>
import { Head, Link, router } from '@statamic/cms/inertia';
import { Header, DocsCallout, Icon, Listing, Badge, DropdownItem } from '@statamic/cms/ui';

const props = defineProps({
    title: String,
    collections: Array,
    columns: Array,
});

const showLink = (collection) => {
    if (collection.configurable) return true
    return collection.available_in_selected_site && collection.enabled_in_selected_site;
};
</script>

<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title :icon="collections[0].icon" />

        <Listing
            :items="collections"
            :columns="columns"
            :allow-search="false"
            :allow-customizing-columns="false"
            @refreshing="() => router.reload()"
        >
            <template #cell-title="{ row: collection }">
                <Link v-if="showLink(collection)" :href="collection.enabled_in_selected_site ? collection.edit_url : collection.config_url" class="flex items-center gap-2 select-none">
                    <Icon :name="collection.icon || 'collections'" />
                    {{ __(collection.title) }}
                </Link>
                <div v-else class="flex items-center gap-2 select-none">
                    <Icon name="security-lock" />
                    {{ __(collection.title) }}
                </div>
            </template>
            <template #cell-status="{ row: collection }">
                <Badge
                    v-if="collection.enabled_in_selected_site"
                    color="green"
                    :text="__('Enabled')"
                    pill
                />
                <Badge
                    v-else
                    color="default"
                    :text="__('Disabled')"
                    pill
                />
            </template>
            <template #prepended-row-actions="{ row: collection }">
                <DropdownItem v-if="collection.enabled_in_selected_site" :text="__('Edit')" icon="edit" :href="collection.edit_url" />
                <DropdownItem v-if="collection.configurable" :text="__('Configure')" icon="cog" :href="collection.config_url" />
            </template>
        </Listing>
        <DocsCallout :topic="__('Collection Defaults')" url="https://advanced-seo.michaelaerni.ch/usage/settings-and-defaults" />
    </div>
</template>
