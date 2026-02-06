<script setup>
import { computed } from 'vue';
import PreviewImage from './PreviewImage.vue';

const props = defineProps({
    meta: { type: Object, required: true },
    seo: { type: Object, required: true },
});

const title = computed(() => props.seo.resolve('seo_og_title'));
const description = computed(() => props.seo.resolve('seo_og_description'));
const image = computed(() => props.seo.resolveTwitterImage());
</script>

<template>
    <div class="w-full max-w-[515px]">

        <!-- Summary card: horizontal layout with small square image -->
        <div v-if="props.meta.twitterCard === 'summary'" class="flex overflow-hidden border border-gray-200 bg-gray-50 dark:border-gray-700 rounded-xl dark:bg-gray-900">
            <PreviewImage :image :width="144" :height="144" class="w-[130px]! shrink-0" />
            <div class="border-l border-gray-200 dark:border-gray-700" />
            <div class="flex flex-col justify-center w-full min-w-0 gap-1 p-3">
                <div class="text-[15px] leading-5 text-gray-500">{{ meta.domain }}</div>
                <div v-if="title" class="text-[15px] leading-5 text-gray-925 dark:text-gray-300 line-clamp-1">{{ title }}</div>
                <div v-else class="w-3/4 h-5 bg-gray-200 rounded dark:bg-gray-800" />
                <div v-if="description" class="text-[15px] leading-5 text-gray-500 line-clamp-2">{{ description }}</div>
                <div v-else class="flex flex-col w-full gap-1">
                    <div class="h-[18px] w-full bg-gray-200 dark:bg-gray-800 rounded" />
                    <div class="h-[18px] w-3/4 bg-gray-200 dark:bg-gray-800 rounded" />
                </div>
            </div>
        </div>

        <!-- Summary large image: image with title overlay -->
        <template v-else>
            <div class="relative overflow-hidden border border-gray-200 dark:border-gray-700 rounded-2xl">
                <PreviewImage :image :width="1200" :height="630" />
                <div class="absolute flex items-center h-5 px-2 rounded left-3 bottom-3 bg-black/77" :class="title ? 'max-w-[calc(100%-24px)]' : 'w-48'">
                    <span v-if="title" class="text-[13px] text-white truncate">{{ title }}</span>
                </div>
            </div>
            <div class="mt-1 text-[13px] leading-4 text-gray-500">From {{ meta.domain }}</div>
        </template>

    </div>
</template>
