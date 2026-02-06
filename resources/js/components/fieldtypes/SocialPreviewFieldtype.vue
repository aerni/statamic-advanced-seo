<script setup>
import { Fieldtype } from '@statamic/cms';
import { useSeoValues } from '../../composables/useSeoValues.js';
import { useSeoAssets } from '../../composables/useSeoAssets.js';
import FacebookPreview from './previews/FacebookPreview.vue';
import XPreview from './previews/XPreview.vue';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const seo = { ...useSeoValues(), ...useSeoAssets() };
</script>

<template>
    <div class="flex flex-wrap items-start mt-4 gap-4 sm:gap-5!">
        <FacebookPreview :meta :seo />
        <XPreview :meta :seo />
    </div>
</template>
