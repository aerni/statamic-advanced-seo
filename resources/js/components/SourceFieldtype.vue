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
            :read-only="!isCustomSource || isReadOnly"
            handle="source_value"
            @meta-updated="isCustomSource ? updateFieldMeta : undefined"
            @update:modelValue="isCustomSource ? updateCustomFieldValue : undefined"
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

    data() {
        return {
            autoBindChangeWatcher: false,
            changeWatcherWatchDeep: false,
            customValue: null,
        }
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
            return this.value.value
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
            const sections = this.store.blueprint.tabs.flatMap(tab => tab.sections)
            const fields = sections.flatMap(section => section.fields)

            return fields.find(field => field.handle === this.autoFieldHandle)?.display
        },

        autoFieldValue() {
            const value = this.store.values[this.autoFieldHandle]

            return typeof value === 'object' && value !== null
                ? value.value
                : value
        },

        autoFieldHandle() {
            return this.config.auto
        },

        fieldIsSynced() {
            return this.$parent.$parent.isSynced
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

        site() {
            return this.store.site
        },

        store() {
            // TODO: This should watch the publish container's site instead
            // return this.$store.state.publish.base
            return this.publishContainer
        },
    },

    watch: {
        autoFieldValue() {
            this.updateAutoFieldValue()
        },

        fieldIsSynced(value) {
            if (value === true) this.updateAutoFieldValue()
        },

        fieldSource(source) {
            // console.log('Update source:', source)
            if (source === SOURCE_TYPES.AUTO) this.updateFieldValue(this.autoFieldValue)
            if (source === SOURCE_TYPES.DEFAULT) this.updateFieldValue(this.fieldDefault)
            if (source === SOURCE_TYPES.CUSTOM) this.updateFieldValue((this.customValue === null) ? this.fieldDefault : this.customValue)
        },

        site() {
            this.customValue = null
            this.updateAutoFieldValue()
        },
    },

    mounted() {
        this.updateAutoFieldValue()
        if (this.fieldSource === SOURCE_TYPES.CUSTOM) this.updateCustomValue(this.fieldValue)
    },

    methods: {

        updateAutoFieldValue() {
            if (this.fieldSource === SOURCE_TYPES.AUTO) this.value.value = this.autoFieldValue
        },

        updateFieldSource(source) {
            if (this.fieldSource !== source) {
                this.value.source = source
            }
        },

        updateFieldValue(value) {
            // console.log('Update value:', value)
            this.value.value = value
            this.update(this.value)
        },

        updateCustomFieldValue(value) {
            this.updateCustomValue(value)
            this.updateFieldValue(value)
        },

        updateCustomValue(value) {
            // console.log('Update custom:', value)
            this.customValue = value
        },

        updateFieldMeta(meta) {
            this.meta.meta = meta || this.fieldMeta
        },

    },

}
</script>
