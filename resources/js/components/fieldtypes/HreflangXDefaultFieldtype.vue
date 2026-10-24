<script setup>
import { Fieldtype } from '@statamic/cms';
import LockedSiteSelect from '../ui/LockedSiteSelect.vue';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update } = Fieldtype.use(emit, props);

defineExpose(expose);
</script>

<template>
    <LockedSiteSelect
        :options="meta.options"
        :model-value="value"
        @update:model-value="update"
    />
</template>
