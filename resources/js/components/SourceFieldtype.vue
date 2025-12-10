<template>
    <div class="mt-4 space-y-2">

        <ButtonGroup>
            <Button
                v-for="(option, index) in sourceOptions"
                :key="option.value"
                :text="option.label || option.value"
                size="xs"
                :variant="fieldSource === option.value ? 'pressed' : 'default'"
                :disabled="isReadOnly"
                @click="updateFieldSource(option.value)"
            />
        </ButtonGroup>

        <Component
            :is="fieldComponent"
            :name="name"
            :config="fieldConfig"
            :meta="fieldMeta"
            :value="fieldValue"
            :read-only="isReadOnly || !isCustomSource"
            handle="source_value"
            @meta-updated="updateFieldMeta"
            @update:model-value="updateFieldValue"
        />

        <Description
            v-if="!isCustomSource"
            :text="sourceDescription"
        />

    </div>
</template>

<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Button, ButtonGroup, Description } from '@statamic/cms/ui';

const SOURCE_TYPES = {
    AUTO: 'auto',
    DEFAULT: 'default',
    CUSTOM: 'custom',
};

export default {
    mixins: [Fieldtype],

    components: {
        Button,
        ButtonGroup,
        Description,
    },

    computed: {

        fieldSource() {
            return this.value.source
        },

        isCustomSource() {
            return this.fieldSource === SOURCE_TYPES.CUSTOM
        },

        fieldDefault() {
            return this.meta.default
        },

        fieldValue() {
            const values = {
                [SOURCE_TYPES.AUTO]: this.autoFieldValue,
                [SOURCE_TYPES.DEFAULT]: this.fieldDefault,
                [SOURCE_TYPES.CUSTOM]: this.value.value,
            }

            return values[this.fieldSource]
        },

        fieldComponent() {
            const type = this.config.field.type
            const component = this.config.field.component
            const field = component || type // Use the component name if it's an entries fieldtype.

            return field.replace('.', '-') + '-fieldtype'
        },

        fieldConfig() {
            return this.config.field
        },

        fieldMeta() {
            return this.meta.meta
        },

        autoFieldDisplay() {
            const sections = this.publishContainer.blueprint.tabs.flatMap(tab => tab.sections)
            const fields = sections.flatMap(section => section.fields)

            return fields.find(field => field.handle === this.autoFieldHandle)?.display
        },

        autoFieldValue() {
            const value = this.publishContainer.values[this.autoFieldHandle]

            return typeof value === 'object' && value !== null
                ? value.value
                : value
        },

        autoFieldHandle() {
            return this.config.auto
        },

        sourceOptions() {
            const options = [
                { label: __('advanced-seo::messages.field_sources.default'), value: SOURCE_TYPES.DEFAULT },
                { label: __('advanced-seo::messages.field_sources.custom'), value: SOURCE_TYPES.CUSTOM },
            ]

            if (this.autoFieldHandle) {
                options.unshift({ label: __('advanced-seo::messages.field_sources.auto'), value: SOURCE_TYPES.AUTO })
            }

            if (this.config.options) {
                return options.filter(option => this.config.options.includes(option.value))
            }

            return options
        },

        sourceDescription() {
            const descriptions = {
                [SOURCE_TYPES.AUTO]: () => __('advanced-seo::messages.field_source_description.auto', {
                    title: this.autoFieldDisplay,
                    handle: this.autoFieldHandle,
                }),
                [SOURCE_TYPES.DEFAULT]: () => __('advanced-seo::messages.field_source_description.defaults', {
                    title: this.meta.title,
                }),
                [SOURCE_TYPES.CUSTOM]: () => '',
            }

            return descriptions[this.fieldSource]?.() || ''
        },
    },

    methods: {

        updateFieldSource(source) {
            if (this.fieldSource === source) return
            this.update({ source: source, value: this.value.value })
        },

        updateFieldValue(value) {
            this.update({ source: this.fieldSource, value: value})
        },

        updateFieldMeta(meta) {
            this.updateMeta({ ...this.meta, meta: meta || this.fieldMeta })
        },

    },

}
</script>
