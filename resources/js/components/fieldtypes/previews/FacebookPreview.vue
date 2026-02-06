<script setup>
import { computed } from 'vue';
import PreviewImage from './PreviewImage.vue';

const props = defineProps({
    meta: { type: Object, required: true },
    seo: { type: Object, required: true },
});

const title = computed(() => props.seo.resolve('seo_og_title'));
const image = computed(() => props.seo.resolveOgImage());
</script>

<template>
    <div class="w-full max-w-[680px] overflow-hidden bg-gray-50 border border-gray-200 dark:border-gray-700 dark:bg-gray-900">
        <PreviewImage :image :width="meta.imagePresets.open_graph.width" :height="meta.imagePresets.open_graph.height" />
        <div class="px-3.5 py-2.5 border-t border-gray-200 dark:border-gray-700">
            <div class="text-[13px] leading-4 text-gray-500 uppercase tracking-wide mb-1">{{ meta.domain }}</div>
            <div v-if="title" class="text-[17px] leading-5 font-medium text-gray-925 dark:text-gray-300 line-clamp-2">{{ title }}</div>
            <div v-else class="flex flex-col gap-1">
                <div class="w-full h-[18px] bg-gray-200 rounded dark:bg-gray-800" />
                <div class="w-3/4 h-[18px] bg-gray-200 rounded dark:bg-gray-800" />
            </div>
        </div>
    </div>
</template>
