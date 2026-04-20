<script setup>
import { Head, useArchitecturalBackground } from '@statamic/cms/inertia';
import { Icon, Badge, Panel, PanelFooter, Card, EmptyStateItem, DocsCallout } from '@statamic/cms/ui';

const props = defineProps({
    groups: Array,
    advancedSeo: Object,
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

    <Panel class="max-w-md mx-auto">
        <Card>
            <ul class="flex flex-wrap [:has(>&)]:p-1.5">
                <EmptyStateItem
                    v-for="item in groups" :key="item.type"
                    :href="item.url"
                    :icon="item.icon"
                    :heading="__(`advanced-seo::messages.${item.type}`)"
                    :description="__(`advanced-seo::messages.${item.type}_description`)"
                />
            </ul>
        </Card>
        <PanelFooter v-if="advancedSeo.promoteUpgrade">
            <div class="pt-2 pb-3 text-center">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('advanced-seo::messages.pro_features') }}</p>
                <p
                    class="mt-0.5 text-xs text-gray-600 dark:text-gray-300"
                    v-html="__('advanced-seo::messages.pro_features_instructions')"
                />
                <div class="mt-5 flex flex-wrap items-center justify-center gap-1.5 select-none">
                    <Badge
                        v-for="feature in advancedSeo.proFeatures"
                        :key="feature.title"
                        :icon="feature.icon"
                        :text="feature.title"
                        :href="feature.url"
                        target="_blank"
                        color="white"
                    />
                </div>
            </div>
        </PanelFooter>
    </Panel>

    <DocsCallout :topic="__('Advanced SEO')" url="https://advanced-seo.michaelaerni.ch" />
</template>
