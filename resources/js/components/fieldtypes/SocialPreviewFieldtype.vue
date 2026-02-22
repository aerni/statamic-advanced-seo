<script setup>
import { computed, watchEffect } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { useSeoValues } from '../../composables/useSeoValues.js';
import { useSeoAssets } from '../../composables/useSeoAssets.js';
import { provideSocialPreview } from '../../composables/useSocialPreview.js';
import FacebookPreview from './previews/FacebookPreview.vue';
import XPreview from './previews/XPreview.vue';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const seo = useSeoValues();
const assets = useSeoAssets();

const imageTemplateUrl = computed(() => assets.resolveImageTemplateUrl());
const image = computed(() => assets.resolveOgImage());
const instructions = computed(() => {
    if (!assets.isGeneratorEnabled()) {
        return null;
    }

    return imageTemplateUrl.value
        ? __('advanced-seo::messages.social_image_updates_on_save')
        : __('advanced-seo::messages.social_image_generates_on_first_save');
});

provideSocialPreview({ meta: props.meta, seo, imageTemplateUrl, image });

watchEffect(() => {
    props.config.instructions = instructions.value;
});
</script>

<template>
    <div class="flex flex-wrap items-start mt-4 gap-4 sm:gap-5!">
        <FacebookPreview />
        <XPreview />
    </div>
</template>
