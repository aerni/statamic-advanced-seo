<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Alert } from '@statamic/cms/ui';
import { usePublishFields } from '../../composables/usePublishFields.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const fieldtype = Fieldtype.use(emit, props);
defineExpose(fieldtype.expose);

const { getFieldValue } = usePublishFields();

const show = computed(() => {
    if (props.meta.show) return true;

    switch (props.config.alert) {
        case 'indexing_disabled': return getFieldValue('seo_noindex');
        default: return false;
    }
});

const wrapper = ref(null);

function showField() {
    wrapper.value.closest('.alert-fieldtype').style.display = show.value ? '' : 'none';
}

onMounted(showField);
watch(show, showField);
</script>

<template>
    <div ref="wrapper">
        <Alert variant="warning" :text="props.meta.message" />
    </div>
</template>
