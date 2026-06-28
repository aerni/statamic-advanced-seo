<script setup>
import { ref, useTemplateRef } from 'vue';
import { Head, router } from '@statamic/cms/inertia';
import { Header, Button, Badge, Listing, DropdownItem } from '@statamic/cms/ui';

const props = defineProps({
    title: String,
    createUrl: String,
    listingUrl: String,
    canCreate: Boolean,
});

const listing = useTemplateRef('listing');
</script>

<template>
    <Head :title />

    <Header :title>
        <Button v-if="canCreate" :href="createUrl" :text="__('advanced-seo::messages.redirect_create_title')" variant="primary" />
    </Header>

    <Listing
        ref="listing"
        :url="listingUrl"
        :allow-presets="false"
        sort-column="source"
        sort-direction="asc"
        preferences-prefix="advanced-seo.redirects"
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
        <template #cell-type="{ row: redirect }">
            {{ redirect.type_label }}
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
            <DropdownItem v-if="redirect.editable" :text="__('Edit')" icon="edit" :href="redirect.edit_url" />
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
