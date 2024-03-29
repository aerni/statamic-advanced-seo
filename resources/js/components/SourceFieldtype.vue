<template>

    <div class="flex flex-col seo-mt-4">

        <div class="self-start button-group-fieldtype-wrapper">
            <div class="seo-h-auto btn-group source-btn-group">
                <button class="h-auto seo-text-[12px] seo-px-2 seo-py-1 btn"
                    v-for="(option, index) in sourceOptions"
                    :key="index"
                    ref="button"
                    :name="name"
                    @click="updateFieldSource($event.target.value)"
                    :value="option.value"
                    :class="{'active': fieldSource === option.value}"
                    :disabled="isReadOnly"
                    v-text="option.label || option.value"
                />
            </div>
        </div>

        <div class="seo-mt-2.5">
            <div v-if="fieldSource === 'custom'">
                <component
                    :is="fieldComponent"
                    :name="name"
                    :config="fieldConfig"
                    :meta="fieldMeta"
                    :value="fieldValue"
                    :read-only="isReadOnly"
                    handle="source_value"
                    @meta-updated="updateFieldMeta"
                    @input="updateCustomFieldValue">
                </component>
            </div>
            <div v-else>
                <component
                    :is="fieldComponent"
                    :name="name"
                    :config="fieldConfig"
                    :meta="fieldMeta"
                    :value="fieldValue"
                    read-only="true"
                    handle="source_value">
                </component>
                <div class="mt-2 mb-0 help-block">
                    <span
                        v-if="fieldSource === 'auto'"
                        v-html="__('advanced-seo::messages.field_source_description.auto', {title: this.autoFieldDisplay, handle: this.autoFieldHandle})"
                    ></span>
                    <span
                        v-else
                        v-html="__('advanced-seo::messages.field_source_description.defaults', {title: this.meta.title})"
                    ></span>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {

    mixins: [Fieldtype],

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

        fieldDefault() {
            return this.meta.default
        },

        fieldValue() {
            return this.value.value
        },

        fieldComponent() {
            let type = this.config.field.type
            let component = this.config.field.component
            let field = component || type // Use the component name if it's an entries fieldtype.

            return field.replace('.', '-') + '-fieldtype'
        },

        fieldConfig() {
            return this.config.field
        },

        fieldMeta() {
            return this.meta.meta
        },

        autoFieldDisplay() {
            let sections = this.store.blueprint.tabs.flatMap(tab => tab.sections)
            let fields = sections.flatMap(section => section.fields)

            return _.find(fields, {'handle': this.autoFieldHandle}).display
        },

        autoFieldValue() {
            let value = this.store.values[this.autoFieldHandle]

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
            let options = [
                { label: __('advanced-seo::messages.field_sources.default'), value: 'default' },
                { label: __('advanced-seo::messages.field_sources.custom'), value: 'custom' },
            ]

            if (this.autoFieldHandle) {
                options.unshift({ label: __('advanced-seo::messages.field_sources.auto'), value: 'auto' })
            }

            if (this.config.options) {
                return options.filter(option => this.config.options.includes(option.value))
            }

            return options
        },

        site() {
            return this.store.site
        },

        store() {
            return this.$store.state.publish.base
        }

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
            if (source === 'auto') this.updateFieldValue(this.autoFieldValue)
            if (source === 'default') this.updateFieldValue(this.fieldDefault)
            if (source === 'custom') this.updateFieldValue((this.customValue === null) ? this.fieldDefault : this.customValue)
        },

        site() {
            this.customValue = null
            this.updateAutoFieldValue()
        },
    },

    mounted() {
        this.updateAutoFieldValue()
        if (this.fieldSource === 'custom') this.updateCustomValue(this.fieldValue)
    },

    methods: {

        updateAutoFieldValue() {
            if (this.fieldSource === 'auto') this.value.value = this.autoFieldValue
        },

        updateFieldSource(source) {
            if (this.fieldSource !== source) this.value.source = source
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
