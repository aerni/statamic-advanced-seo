<template>

    <div class="flex flex-col seo-mt-4">

        <div class="self-start button-group-fieldtype-wrapper">
            <div class="seo-h-auto btn-group source-btn-group">
                <button class="btn seo-h-auto seo-text-xs" style="padding: 1px 5px;"
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
                <div class="mt-1 help-block">
                    <span v-if="fieldSource === 'auto'">
                        The value is inherited from the <code>{{ autoFieldDisplay }} ({{ autoFieldHandle }})</code> field.
                    </span>
                    <span v-else>
                        The value is inherited from the <code>{{ this.meta.title }}</code> defaults.
                    </span>
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

        fieldIsSynced() {
            return this.$parent.$parent.isSynced
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
            let fields = this.store.blueprint.sections.flatMap(section => section.fields)

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

        sourceOptions() {
            let options = [
                { label: __('advanced-seo::messages.default'), value: 'default' },
                { label: __('advanced-seo::messages.custom'), value: 'custom' },
            ]

            if (this.autoFieldHandle) {
                options.unshift({ label: 'Auto', value: 'auto' })
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

        fieldSource(source) {
            // console.log('Update source:', source)
            if (source === 'auto') this.updateFieldValue(this.autoFieldValue)
            if (source === 'default') this.updateFieldValue(this.fieldDefault)
            if (source === 'custom') this.updateFieldValue(this.customValue)
        },

        site() {
            this.customValue = null
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
            if (_.isEqual(value, this.fieldValue)) {
                return
            }

            // console.log('Update value:', value)
            this.value.value = value
            this.update(this.value)
        },

        updateCustomValue(value) {
            // console.log('Update custom:', value)
            this.customValue = value
        },

        updateFieldMeta(meta) {
            this.meta.meta = meta || this.fieldMeta
        },

        updateCustomFieldValue(value) {
            if (_.isEqual(value, this.fieldValue)) {
                return
            }

            this.updateCustomValue(value)
            this.updateFieldValue(value)
            this.updateFieldSource('custom')
        },

    },

}
</script>
