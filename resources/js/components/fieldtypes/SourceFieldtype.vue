<template>
    <div class="mt-4 space-y-2">

        <div>
            <ButtonGroup>
                <Button
                    v-for="(source) in fieldSources"
                    :key="source.value"
                    :text="source.label"
                    size="xs"
                    :variant="fieldSource === source.value ? 'pressed' : 'default'"
                    :disabled="isReadOnly"
                    @click="updateFieldSource(source.value)"
                />
            </ButtonGroup>
        </div>

        <div>
            <Component
                :is="fieldComponent"
                :name="name"
                :config="fieldConfig"
                :meta="fieldMeta"
                :value="fieldValue"
                :read-only="isReadOnly || !isCustomSource"
                handle="source_value"
                @update:meta="updateFieldMeta"
                @update:value="updateFieldValue"
            />
        </div>

        <div v-if="!isCustomSource">
            <Description :text="sourceDescription" />
        </div>


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

        autoFieldValue() {
            const value = this.publishContainer.values[this.autoFieldHandle]
            return value && typeof value === 'object' ? value.value : value
        },

        defaultFieldValue() {
            return this.meta.default
        },

        customFieldValue() {
            return this.value.value
        },

        fieldValue() {
            const values = {
                [SOURCE_TYPES.AUTO]: this.autoFieldValue,
                [SOURCE_TYPES.DEFAULT]: this.defaultFieldValue,
                [SOURCE_TYPES.CUSTOM]: this.customFieldValue,
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

        autoFieldHandle() {
            return this.config.auto
        },

        fieldSources() {
            const sources = [
                {
                    value: SOURCE_TYPES.DEFAULT,
                    label: __('advanced-seo::messages.field_sources.default'),
                    description: __('advanced-seo::messages.field_source_description.defaults', {
                        title: this.meta.title,
                    }),
                },
                {
                    value: SOURCE_TYPES.CUSTOM,
                    label: __('advanced-seo::messages.field_sources.custom'),
                    description: '',
                },
            ]

            if (this.autoFieldHandle) {
                sources.unshift({
                    value: SOURCE_TYPES.AUTO,
                    label: __('advanced-seo::messages.field_sources.auto'),
                    description: __('advanced-seo::messages.field_source_description.auto', {
                        title: this.autoFieldDisplay,
                        handle: this.autoFieldHandle,
                    }),
                })
            }

            if (this.config.options) {
                return sources.filter(source => this.config.options.includes(source.value))
            }

            return sources
        },

        sourceDescription() {
            return this.fieldSources.find(source => source.value === this.fieldSource).description
        },
    },

    methods: {

        updateFieldSource(source) {
            if (this.fieldSource === source) return
            this.update({ source: source, value: this.customFieldValue })
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
