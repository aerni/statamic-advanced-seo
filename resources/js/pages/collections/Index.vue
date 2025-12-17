<script setup>
import { Head, Link, router } from '@statamic/cms/inertia';
import { Header, DocsCallout, Icon, Listing, Badge, DropdownItem } from '@statamic/cms/ui';

const props = defineProps({
    title: String,
    collections: Array,
    columns: Array,
});
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
                <Link :href="collection.status === 'enabled' ? collection.edit_url : collection.config_url" class="flex items-center gap-2">
                    <Icon :name="collection.icon || 'collections'" />
                    {{ __(collection.title) }}
                </Link>
            </template>
            <template #cell-status="{ row: collection }">
                <div class="flex items-center gap-2 sm:gap-3">
                    <Badge
                        v-if="collection.status === 'enabled'"
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
                </div>
            </template>
            <template #prepended-row-actions="{ row: collection }">
                <DropdownItem v-if="collection.status === 'enabled'" :text="__('Edit')" icon="edit" :href="collection.edit_url" />
                <DropdownItem v-if="collection.configurable" :text="__('Configure')" icon="cog" :href="collection.config_url" />
            </template>
        </Listing>

        <DocsCallout :topic="__('Collection Defaults')" url="https://advanced-seo.michaelaerni.ch/usage/settings-and-defaults" />
    </div>
</template>
