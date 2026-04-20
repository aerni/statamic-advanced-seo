<script setup>
import { computed } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { useSeoValues } from '../../composables/useSeoValues.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const seo = useSeoValues();

const siteName = computed(() => props.meta.siteName);

const title = computed(() => seo.truncate(seo.resolve('seo_title'), 60));

const description = computed(() => seo.truncate(seo.resolve('seo_description'), 160));

const breadcrumbs = computed(() => {
    if (props.meta.uri === '/') return [props.meta.domain];

    return [
        props.meta.domain,
        ...props.meta.breadcrumbs.slice(0, -1),
        seo.resolve('title'),
    ];
});
</script>

<template>
    <div class="mt-4 max-w-[700px] p-5! bg-gray-50 border border-gray-200 rounded-lg dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center gap-3 mb-1.5">
            <div class="flex items-center justify-center rounded-full size-[26px] shrink-0 bg-gray-100 border border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                <img v-if="meta.favicon" :src="meta.favicon" class="size-[18px] rounded-sm" />
                <svg v-else class="size-3.5 text-gray-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zM4 12c0-.93.16-1.823.454-2.652L7.96 12.85 7.96 13.9a1.053 1.053 0 001.052 1.052v2.063A8.01 8.01 0 014 12zm13.072 5.196A1.044 1.044 0 0016.2 16.5h-.496a1.053 1.053 0 01-1.052-1.052v-1.58a.526.526 0 00-.527-.526H10.06a.526.526 0 01-.527-.527v-1.052c0-.291.236-.527.527-.527h2.104c.291 0 .527-.236.527-.527V9.657c0-.291.236-.527.527-.527h.527c.581 0 1.052-.471 1.052-1.052V7.66A8.012 8.012 0 0120 12a7.97 7.97 0 01-2.928 5.196z" />
                </svg>
            </div>
            <div class="min-w-0">
                <div v-if="siteName" class="text-[14px] leading-[20px] text-gray-925 dark:text-gray-300">{{ siteName }}</div>
                <div v-else class="flex items-center h-[20px]"><div class="h-2.5 w-24 bg-gray-200 dark:bg-gray-800 rounded" /></div>
                <div class="text-[12px] leading-[18px] text-gray-600 dark:text-gray-400 truncate">
                    <template v-for="(breadcrumb, i) in breadcrumbs" :key="i">
                        <span v-if="i > 0"> › </span>
                        <span v-if="breadcrumb">{{ breadcrumb }}</span>
                        <span v-else class="inline-block w-12 h-2 align-middle bg-gray-200 rounded dark:bg-gray-800" />
                    </template>
                </div>
            </div>
        </div>
        <div class="text-[20px] leading-[26px] text-[#1a0dab] dark:text-[#8ab4f8] mb-1 line-clamp-2 whitespace-pre-wrap">
            <template v-if="title">{{ title }}</template>
            <template v-else><span class="inline-block w-32 h-5 align-middle bg-gray-200 rounded dark:bg-gray-800" /></template>
        </div>
        <div v-if="description" class="text-sm leading-[22px] text-gray-600 dark:text-gray-400 line-clamp-2 whitespace-pre-wrap">{{ description }}</div>
        <div v-else>
            <div class="flex items-center h-[22px]"><span class="w-full h-3 bg-gray-200 rounded dark:bg-gray-800" /></div>
            <div class="flex items-center h-[22px]"><span class="w-2/3 h-3 bg-gray-200 rounded dark:bg-gray-800" /></div>
        </div>
    </div>
</template>
