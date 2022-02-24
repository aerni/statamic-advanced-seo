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
            <component
                :is="fieldComponent"
                :name="name"
                :config="fieldConfig"
                :meta="fieldMeta"
                :value="fieldValue"
                :read-only="isReadOnly"
                handle="source_value"
                @input="updateFieldValue">
            </component>
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

        sourceOptions() {
            return [
                { label: __('advanced-seo::messages.default'), value: 'default' },
                { label: __('advanced-seo::messages.custom'), value: 'custom' },
            ]
        },

        site() {
            return this.$store.state.publish.base.site;
        }

    },

    watch: {
        fieldIsSynced(value) {
            // Use isEqual because the data can be of different types.
            // With the code fieldtype for instance the data is an object.

            if (value === true && _.isEqual(this.value.value, this.fieldDefault)) {
                this.value.source = 'default'
            } else if (value === true && ! _.isEqual(this.value.value, this.fieldDefault)) {
                this.value.source = 'custom'
            }
        },

        site() {
            this.customValue = null;
        },
    },

    methods: {

        updateFieldSource(source) {
            if (this.value.source === source) {
                return;
            }

            this.value.source = source

            if (source === 'default') {
                // Save the value so that we can restore it if the user switches back to custom.
                this.customValue = this.value.value
                this.updateFieldValue(this.fieldDefault)
            }

            if (source === 'custom') {
                this.updateFieldValue(this.customValue || this.fieldDefault)
            }
        },

        updateFieldValue(value) {
            this.value.value = value;

            if (this.fieldValue !== this.fieldDefault) {
                this.value.source = 'custom'
            }

            this.update(this.value);
        },

    },

}
</script>
