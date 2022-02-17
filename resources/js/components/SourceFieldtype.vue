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
            return this.config.field.type.replace('.', '-') + '-fieldtype'
        },

        fieldConfig() {
            return this.config.field
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
            if (value === true && this.value === null) {
                this.meta.source = 'default'
            } else if (value === true && this.value !== null) {
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
                this.updateFieldValue('@default')
            }

            if (value === 'custom') {
                let value = this.tempValue || this.fieldDefault
                this.updateFieldValue(value)
            }
        },

        updateFieldValue(value) {
            this.update(value)
        },

    },

}
</script>
