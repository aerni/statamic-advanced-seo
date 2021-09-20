<template>
    <div>
        <!-- <div class="publish-fields">
            <publish-field
                v-for="field in fields"
                :key="field.handle"
                :config="field"
                :value="value[field.handle]"
                :meta="meta.meta[field.handle]"
                class="form-group"
                @meta-updated="metaUpdated(field.handle, $event)"
                @focus="$emit('focus')"
                @blur="$emit('blur')"
                @input="updateKey(field.handle, $event)"
            />
        </div> -->

        <publish-fields-container>
            <publish-field
                v-for="field in fields"
                v-show="showField(field)"
                :key="field.handle"
                :config="field"
                :value="values[field.handle]"
                :meta="meta[field.handle]"
                :errors="errors[field.handle]"
                :read-only="readOnly"
                :can-toggle-label="canToggleLabels"
                :name-prefix="namePrefix"
                @input="updated(field.handle, $event)"
                @meta-updated="$emit('meta-updated', field.handle, $event)"
                @synced="$emit('synced', field.handle)"
                @desynced="$emit('desynced', field.handle)"
                @focus="$emit('focus')"
                @blur="$emit('blur')"
            />
        </publish-fields-container>
    </div>

</template>

<style>
    /* .advanced_seo-fieldtype > .field-inner > label {
        display: none !important;
    }
    .advanced_seo-fieldtype,
    .advanced_seo-fieldtype .publish-fields {
        padding: 0 !important;
    } */
</style>

<script>
    import ValidatesFieldConditions from "../../../vendor/statamic/cms/resources/js/components/field-conditions/ValidatorMixin.js";

    export default {
        mixins: [Fieldtype, ValidatesFieldConditions],

        inject: ["storeName"],

        // computed: {
        //     fields() {
        //         return _.chain(this.meta.fields)
        //             .map(field => {
        //                 return {
        //                     handle: field.handle,
        //                     ...field.field
        //                 };
        //             })
        //             .values()
        //             .value();
        //     }
        // },

        computed: {
            state() {
                return this.$store.state.publish[this.storeName];
            },

            values() {
                // merge default values with "real" values
                return { ...this.meta.defaults, ...this.value };
            },

            errors() {
                return this.state.errors;
            },

            fields() {
                return _.chain(this.meta.fields)
                    .map(field => {
                        return {
                            handle: field.handle,
                            ...field.field
                        };
                    })
                    .values()
                    .value();
            }

            // fields() {
            //     return this.config.fields;
            // },
        },

        methods: {
            updated(handle, value) {
                let group = JSON.parse(JSON.stringify(this.values));
                group[handle] = value;
                this.update(group);
            },
            // updateKey(handle, value) {
            //     let seoValue = this.value;
            //     Vue.set(seoValue, handle, value);
            //     this.update(seoValue);
            // }
        },
    }
</script>
