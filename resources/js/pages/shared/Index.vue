<script setup>
import { Head, Link, router } from '@statamic/cms/inertia';
import { Header, DocsCallout, Icon, Listing, Badge, DropdownItem } from '@statamic/cms/ui';

const props = defineProps({
    title: String,
    icon: String,
    items: Array,
    columns: Array,
    // docs: Array,
});

const showLink = (item) => {
    if (item.configurable) return true
    return item.enabled;
};
</script>

<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title :icon />

        <Listing
            v-if="items.length"
            :items="items"
            :columns="columns"
            :allow-search="false"
            :allow-customizing-columns="false"
            @refreshing="() => router.reload()"
        >
            <template #cell-title="{ row: item }">
                <Link v-if="showLink(item)" :href="item.enabled ? item.localization_url : item.config_url" class="flex items-center gap-2 select-none">
                    <Icon :name="item.icon" />
                    {{ __(item.title) }}
                </Link>
                <div v-else class="flex items-center gap-2 opacity-50 select-none">
                    <Icon :name="item.icon" />
                    {{ __(item.title) }}
                </div>
            </template>
            <template #cell-status="{ row: item }">
                <Badge
                    v-if="item.enabled"
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
            <template #prepended-row-actions="{ row: item }">
                <DropdownItem v-if="item.enabled" :text="__('Edit')" icon="edit" :href="item.localization_url" />
                <DropdownItem v-if="item.configurable" :text="__('Configure')" icon="cog" :href="item.config_url" />
            </template>
        </Listing>

        <div
            v-else
            class="p-6 text-center text-gray-500 border border-gray-300 border-dashed rounded-lg dark:border-gray-700"
            v-text="__(`No ${title} configured for the selected site`)"
        />

        <!-- <DocsCallout :topic="__(docs.topic)" :url="docs.url" /> -->
    </div>
</template>
