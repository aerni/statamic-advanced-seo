<script setup>
import { Head, useArchitecturalBackground } from '@statamic/cms/inertia';
import { Icon, EmptyStateMenu, EmptyStateItem, DocsCallout } from '@statamic/cms/ui';

const props = defineProps({
    defaults: Array,
});

useArchitecturalBackground();
</script>

<template>
    <Head :title="__('Advanced SEO')" />

    <header class="py-8 pt-16 text-center">
        <h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
            <Icon name="ai-search-spark" class="text-gray-500 size-5.5" />
            {{ __('Advanced SEO') }}
        </h1>
    </header>

    <EmptyStateMenu>
        <EmptyStateItem
            v-for="item in defaults" :key="item.type"
            :href="item.route"
            :icon="item.icon"
            :heading="__(item.title)"
            :description="__(`advanced-seo::messages.${item.type}_description`)"
        />
    </EmptyStateMenu>

    <DocsCallout :topic="__('Advanced SEO')" url="https://advanced-seo.michaelaerni.ch" />
</template>
