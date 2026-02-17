<script setup>
import { computed } from 'vue';
import { useSocialPreview } from '../../../composables/useSocialPreview.js';
import PreviewIframe from './PreviewIframe.vue';
import PreviewImage from './PreviewImage.vue';

const { meta, seo, imageTemplateUrl, image } = useSocialPreview();

const title = computed(() => seo.resolve('seo_og_title'));
</script>

<template>
    <div class="w-full max-w-[680px] overflow-hidden border border-gray-200 dark:border-gray-700">
        <PreviewIframe v-if="imageTemplateUrl"
            :src="imageTemplateUrl"
            :preset="meta.imagePresets.open_graph"
        />
        <PreviewImage v-else
            :image
            :preset="meta.imagePresets.open_graph"
        />
        <div class="px-3.5 py-2.5 border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-[13px] leading-4 text-gray-500 uppercase tracking-wide mb-1">{{ meta.domain }}</div>
            <div v-if="title" class="text-[17px] leading-5 font-medium text-gray-925 dark:text-gray-300 line-clamp-2">{{ title }}</div>
            <div v-else class="flex flex-col gap-1">
                <div class="w-full h-[18px] bg-gray-200 rounded dark:bg-gray-800" />
                <div class="w-3/4 h-[18px] bg-gray-200 rounded dark:bg-gray-800" />
            </div>
        </div>
    </div>
</template>
