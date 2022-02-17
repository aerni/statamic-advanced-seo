<template>

    <div class="flex flex-col">

        <div>
            <component
                :is="fieldComponent"
                :name="name"
                :config="fieldConfig"
                :value="fieldValue"
                :read-only="isReadOnly || fieldSource === 'default'"
                handle="source_value"
                @input="updateCustomValue">
            </component>
        </div>

        <div class="mt-1 button-group-fieldtype-wrapper">
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

    </div>
</template>

<script>
export default {

    mixins: [Fieldtype],

    data() {
        return {
            autoBindChangeWatcher: false,
            changeWatcherWatchDeep: false,
        }
    },

    computed: {

        fieldSource() {
            return this.value.source;
        },

        fieldValue() {
            return this.fieldSource === 'default' ? this.fieldDefault : this.value.value;
        },

        fieldDefault() {
            return this.fieldConfig.default;
        },

        sourceOptions() {
            return [
                { label: __('advanced-seo::messages.default'), value: 'default' },
                { label: __('advanced-seo::messages.custom'), value: 'custom' },
            ]
        },

        fieldComponent() {
            return this.config.field.type.replace('.', '-') + '-fieldtype';
        },

        fieldConfig() {
            return this.config.field;
        },

    },

    methods: {

        sourceChanged(value) {
            this.value.source = value;

            if (value === 'custom' && ! this.value.value) {
                this.updateCustomValue(this.fieldDefault)
            } else {
                this.updateCustomValue(this.value.value)
            }
        },

        updateCustomValue(value) {
            let newValue = this.value;

            newValue.value = value;

            this.update(newValue);
        },

    },

}
</script>
