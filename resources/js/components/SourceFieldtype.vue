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
            <div v-if="fieldSource !== 'auto'">
                <component
                    :is="fieldComponent"
                    :name="name"
                    :config="fieldConfig"
                    :meta="fieldMeta"
                    :value="fieldValue"
                    :read-only="isReadOnly"
                    handle="source_value"
                    @input="updateCustomFieldValue">
                </component>
            </div>
            <div v-else>
                <component
                    :is="fieldComponent"
                    :value="autoFieldValue"
                    read-only="true">
                </component>
                <div class="mt-1 help-block">
                    The value is inherited from <code>{{ autoFieldDisplay }} ({{ autoFieldHandle }})</code>
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
            let fields = this.store.blueprint.sections.flatMap(section => section.fields);

            return _.find(fields, {'handle': this.autoFieldHandle}).display;
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

            return options
        },

        site() {
            return this.store.site;
        },

        store() {
            return this.$store.state.publish.base;
        }

    },

    watch: {
        autoFieldValue() {
            if (this.fieldSource === 'auto') {
                this.updateFieldValue(this.autoFieldValue)
            }
        },

        site() {
            // Reset the temporary custom value when the user switches the site.
            this.customValue = null;
        },
    },

    mounted() {
        if (this.fieldSource === 'auto') {
            this.updateFieldValue(this.autoFieldValue)
        }
    },

    methods: {

        updateFieldSource(source) {
            if (this.value.source === source) {
                return;
            }

            this.value.source = source

            if (source === 'default') {
                this.updateFieldValue(this.fieldDefault)
            }

            if (source === 'custom') {
                this.updateFieldValue(this.customValue || this.fieldDefault)
            }

            if (source === 'auto') {
                this.updateFieldValue(this.autoFieldValue)
            }
        },

        updateFieldValue(value) {
            this.value.value = value;

            this.update(this.value);
        },

        // TODO: This for whatever reason always triggers when changing the source on the code and assets fieldtype.
        // This is the reason the custom value gets reset again. Why does this happen?!
        updateCustomFieldValue(value) {
            this.updateFieldValue(value)

            // Save the value so that we can restore it if the user switches back to custom.
            this.customValue = this.value.value

            if (! _.isEqual(this.fieldValue, this.fieldDefault)) {
                this.value.source = 'custom'
            }
        },

    },

}
</script>
