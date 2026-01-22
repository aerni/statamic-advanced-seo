<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@statamic/cms/inertia';
import { Header, DocsCallout, Icon, Listing, Badge, DropdownItem, DropdownSeparator, ConfirmationModal } from '@statamic/cms/ui';

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

const enable = (item) => router.post(item.enable_url);

const disabling = ref(null);

const disable = (item) => {
    disabling.value = item;
};

const confirmDisable = () => {
    router.post(disabling.value.disable_url, {}, {
        onFinish: () => disabling.value = null,
    });
};

const cancelDisable = () => {
    disabling.value = null;
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
                <DropdownSeparator v-if="item.enabled && item.configurable" />
                <DropdownItem v-if="item.configurable" :text="__('Configure')" icon="cog" :href="item.config_url" />
                <DropdownItem v-if="item.configurable && !item.enabled" :text="__('Enable')" icon="eye" @click="enable(item)" />
                <DropdownItem v-if="item.configurable && item.enabled" :text="__('Disable')" icon="eye-slash" @click="disable(item)" />
            </template>
        </Listing>

        <div
            v-else
            class="p-6 text-center text-gray-500 border border-gray-300 border-dashed rounded-lg dark:border-gray-700"
            v-text="__(`No ${title} configured for the selected site`)"
        />

        <!-- <DocsCallout :topic="__(docs.topic)" :url="docs.url" /> -->

        <ConfirmationModal
            v-if="disabling"
            :open="true"
            :title="__('Disable :title', { title: __(disabling.title) })"
            :buttonText="__('Disable')"
            :danger="true"
            @confirm="confirmDisable"
            @cancel="cancelDisable"
        >
            <p class="text-sm">{{ __('Are you sure you want to disable this item? All SEO data will be deleted.') }}</p>
        </ConfirmationModal>
    </div>
</template>
