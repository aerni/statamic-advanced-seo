import { computed } from 'vue';
import { injectPublishContext } from '@statamic/cms/ui';

export function usePublishFields() {
    const publishContainer = injectPublishContext();

    const fields = computed(() => Object.fromEntries(
        Object.values(publishContainer.blueprint.value.tabs)
            .flatMap(tab => tab.sections)
            .flatMap(section => section.fields)
            .map(field => [field.handle, field])
    ));

    function getField(handle) {
        return fields.value[handle];
    }

    function getFieldRawValue(handle) {
        return publishContainer.values.value[handle];
    }

    function getFieldValue(handle) {
        const raw = getFieldRawValue(handle);

        if (!isSeoField(handle)) {
            return raw;
        }

        if (hasWrappedValue(raw)) {
            return raw.value;
        }

        return raw;
    }

    function getFieldMeta(handle) {
        return publishContainer.meta.value[handle];
    }

    function isSeoField(handle) {
        return getField(handle)?.type === 'seo';
    }

    function hasWrappedValue(raw) {
        return !!raw
            && !Array.isArray(raw)
            && typeof raw === 'object'
            && Object.hasOwn(raw, 'value');
    }

    return {
        fields,
        getField,
        getFieldRawValue,
        getFieldValue,
        getFieldMeta,
        publishContainer,
    };
}
