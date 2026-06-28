<script setup>
import { useTemplateRef, getCurrentInstance } from 'vue';
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Badge, Listing, DropdownItem, DropdownSeparator, Icon, Panel, Card, EmptyStateItem } from '@statamic/cms/ui';

const props = defineProps({
    title: String,
    createUrl: String,
    listingUrl: String,
    canCreate: Boolean,
    filters: Array,
    hasRedirects: Boolean,
});

const listing = useTemplateRef('listing');

const { $axios } = getCurrentInstance().appContext.config.globalProperties;

const toggleEnabled = (url, successMessage) => {
    $axios.post(url).then(() => {
        Statamic.$toast.success(__(successMessage));
        listing.value.refresh();
    });
};
</script>

<template>
    <Head :title />

    <template v-if="hasRedirects">
        <Header :title icon="moved">
            <Button v-if="canCreate" :href="createUrl" :text="__('advanced-seo::messages.redirect_create_title')" variant="primary" />
        </Header>

        <Listing
            ref="listing"
            :url="listingUrl"
            :allow-presets="false"
            :allow-customizing-columns="false"
            sort-column="source"
            sort-direction="asc"
            :filters="filters"
            push-query
        >
            <template #cell-source="{ row: redirect }">
                <a v-if="redirect.editable" :href="redirect.edit_url" class="title-index-field">
                    {{ redirect.source }}
                </a>
                <span v-else class="title-index-field">{{ redirect.source }}</span>

                <resource-deleter
                    v-if="redirect.deletable"
                    :ref="`deleter_${redirect.id}`"
                    :resource="redirect"
                    @deleted="listing.refresh()"
                />
            </template>
            <template #cell-destination="{ row: redirect }">
                <a v-if="redirect.destination_url" :href="redirect.destination_url" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5">
                    <Icon v-if="redirect.destination_is_entry" name="entry" class="shrink-0 size-4 text-gray-500" />
                    <Icon v-else name="external-link" class="shrink-0 size-4 text-gray-500" />
                    <span v-text="redirect.destination" />
                </a>
                <span v-else v-text="redirect.destination" />
            </template>
            <template #cell-type="{ row: redirect }">
                {{ redirect.type_label }}
            </template>
            <template #cell-site="{ row: redirect }">
                {{ redirect.site_name }}
            </template>
            <template #cell-status="{ row: redirect }">
                <Badge
                    v-if="redirect.status"
                    color="green"
                    :text="__('advanced-seo::messages.enabled')"
                    pill
                />
                <Badge
                    v-else
                    color="default"
                    :text="__('Disabled')"
                    pill
                />
            </template>
            <template #prepended-row-actions="{ row: redirect }">
                <DropdownItem
                    v-if="redirect.test_url"
                    :text="__('advanced-seo::messages.test_redirect')"
                    icon="external-link"
                    :href="redirect.test_url"
                    target="_blank"
                    rel="noopener noreferrer"
                />
                <DropdownItem v-if="redirect.editable" :text="__('Edit')" icon="edit" :href="redirect.edit_url" />
                <DropdownSeparator v-if="redirect.editable" />
                <DropdownItem
                    v-if="redirect.editable && redirect.status"
                    :text="__('advanced-seo::messages.disable')"
                    icon="eye-slash"
                    @click="toggleEnabled(redirect.disable_url, 'advanced-seo::messages.redirect_disabled')"
                />
                <DropdownItem
                    v-if="redirect.editable && !redirect.status"
                    :text="__('advanced-seo::messages.enable')"
                    icon="eye"
                    @click="toggleEnabled(redirect.enable_url, 'advanced-seo::messages.redirect_enabled')"
                />
                <DropdownSeparator v-if="redirect.deletable" />
                <DropdownItem
                    v-if="redirect.deletable"
                    :text="__('Delete')"
                    icon="trash"
                    variant="destructive"
                    @click="$refs[`deleter_${redirect.id}`].confirm()"
                />
            </template>
        </Listing>
    </template>

    <template v-else>
        <header class="py-8 pt-16 text-center">
            <h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
                <Icon name="moved" class="text-gray-500 size-5.5" />
                {{ __('advanced-seo::messages.redirects') }}
            </h1>
        </header>

        <Panel class="max-w-md mx-auto">
            <Card>
                <ul class="flex flex-wrap [:has(>&)]:p-1.5">
                    <EmptyStateItem
                        v-if="canCreate"
                        :href="createUrl"
                        icon="moved"
                        :heading="__('advanced-seo::messages.redirect_create_title')"
                        :description="__('advanced-seo::messages.redirect_create_description')"
                    />
                </ul>
            </Card>
        </Panel>
    </template>
</template>
