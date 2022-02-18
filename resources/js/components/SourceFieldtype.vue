<template>

    <div class="flex flex-col seo-mt-4">

        <div class="button-group-fieldtype-wrapper">
            <div class="seo-h-auto btn-group source-btn-group">
                <button class="btn seo-h-auto seo-text-xs" style="padding: 1px 5px;"
                    v-for="(option, index) in sourceOptions"
                    :key="index"
                    ref="button"
                    :name="name"
                    @click="sourceChanged($event.target.value)"
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
                :read-only="isReadOnly || fieldSource === 'default'"
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
            tempValue: '',
        }
    },

    computed: {

        fieldSource() {
            return this.meta.source
        },

        fieldDefault() {
            return this.meta.default
        },

        fieldValue() {
            return this.fieldSource === 'default' ? this.fieldDefault : this.value
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

            if (value === true && _.isEqual(this.value, this.fieldDefault)) {
                this.meta.source = 'default'
            } else if (value === true && ! _.isEqual(this.value, this.fieldDefault)) {
                this.meta.source = 'custom'
            }
        },

        site() {
            this.tempValue = '';
        },
    },

    methods: {

        sourceChanged(value) {
            if (this.meta.source === value) {
                return;
            }

            this.meta.source = value

            if (value === 'default') {
                this.tempValue = this.value
                this.updateFieldValue(this.fieldDefault)
            }

            if (value === 'custom') {
                let value = this.tempValue || this.fieldDefault
                this.meta.meta = this.meta.defaultMeta // TODO: Do I really need this? I just copied it from SEO Pro.
                this.updateFieldValue(value)
            }
        },

        updateFieldValue(value) {
            this.update(value)
        },

    },

}
</script>
