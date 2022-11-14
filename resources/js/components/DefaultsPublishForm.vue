<template>

    <div class="remove-border-bottom">
        <header class="mb-3">
            <breadcrumb :url="computedBreadcrumbs.url" :title="computedBreadcrumbs.text" />

            <div class="flex items-center">
                <h1 class="flex-1" v-text="title" />

                <div class="flex pt-px text-2xs text-grey-60" v-if="readOnly">
                    <svg-icon name="lock" class="w-4 mr-sm -mt-sm" /> {{ __('Read Only') }}
                </div>

                <button
                    v-if="!readOnly"
                    class="ml-2 btn-primary min-w-100"
                    :class="{ 'opacity-25': !canSave }"
                    :disabled="!canSave"
                    @click.prevent="save"
                    v-text="__('Save')" />
            </div>
        </header>

        <publish-container
            ref="container"
            :name="publishContainer"
            :blueprint="fieldset"
            :values="values"
            :reference="initialReference"
            :meta="meta"
            :errors="errors"
            :site="site"
            :localized-fields="localizedFields"
            :is-root="isRoot"
            @updated="values = $event"
        >
            <div slot-scope="{ container, components, setFieldMeta }">
                <component
                    v-for="component in components"
                    :key="component.name"
                    :is="component.name"
                    :container="container"
                    v-bind="component.props"
                />
                <publish-sections
                    :read-only="readOnly"
                    :syncable="hasOrigin"
                    :can-toggle-labels="true"
                    :enable-sidebar="shouldShowSites"
                    @updated="setFieldValue"
                    @meta-updated="setFieldMeta"
                    @synced="syncField"
                    @desynced="desyncField"
                    @focus="container.$emit('focus', $event)"
                    @blur="container.$emit('blur', $event)"
                >
                    <template #actions="{ shouldShowSidebar }">
                        <div class="p-2" v-if="shouldShowSites">
                            <label class="mb-1 font-medium publish-field-label" v-text="__('Sites')" />
                            <div
                                v-for="option in localizations"
                                :key="option.handle"
                                class="flex items-center px-2 py-1 -mx-2 text-sm cursor-pointer"
                                :class="option.active ? 'bg-blue-100' : 'hover:bg-grey-20'"
                                @click="localizationSelected(option)"
                            >
                                <div class="flex items-center flex-1">
                                    {{ option.name }}
                                    <loading-graphic :size="14" text="" class="flex items-center ml-1" style="padding-bottom: 0.05em;" v-if="localizing === option.handle"/>
                                </div>
                                <div class="badge-sm bg-orange" v-if="option.origin" v-text="__('Origin')" />
                                <div class="badge-sm bg-blue" v-if="option.active" v-text="__('Active')" />
                                <div class="badge-sm bg-purple" v-if="option.root && !option.origin && !option.active" v-text="__('Root')" />
                            </div>
                        </div>
                    </template>
                </publish-sections>
            </div>

        </publish-container>
    </div>

</template>

<style scoped>
.remove-border-bottom >>> .publish-sidebar .publish-section-actions {
    border-bottom-width: 0;
}
</style>

<script>
import HasHiddenFields from '../../../vendor/statamic/cms/resources/js/components/data-list/HasHiddenFields';

export default {

    mixins: [
        HasHiddenFields,
    ],

    props: {
        publishContainer: String,
        initialReference: String,
        initialFieldset: Object,
        initialValues: Object,
        initialMeta: Object,
        initialTitle: String,
        initialLocalizations: Array,
        initialLocalizedFields: Array,
        initialHasOrigin: Boolean,
        initialOriginValues: Object,
        initialOriginMeta: Object,
        initialSite: String,
        breadcrumbs: Array,
        initialActions: Object,
        method: String,
        isCreating: Boolean,
        initialReadOnly: Boolean,
        initialIsRoot: Boolean,
        contentType: String,
    },

    data() {
        return {
            actions: this.initialActions,
            saving: false,
            localizing: false,
            fieldset: this.initialFieldset,
            title: this.initialTitle,
            values: _.clone(this.initialValues),
            meta: _.clone(this.initialMeta),
            localizations: _.clone(this.initialLocalizations),
            localizedFields: this.initialLocalizedFields,
            hasOrigin: this.initialHasOrigin,
            originValues: this.initialOriginValues || {},
            originMeta: this.initialOriginMeta || {},
            site: this.initialSite,
            error: null,
            errors: {},
            isRoot: this.initialIsRoot,
            readOnly: this.initialReadOnly,
        }
    },

    computed: {

        shouldShowSites() {
            return this.localizations.length > 1;
        },

        hasErrors() {
            return this.error || Object.keys(this.errors).length;
        },

        somethingIsLoading() {
            return ! this.$progress.isComplete();
        },

        canSave() {
            return !this.readOnly && this.isDirty && !this.somethingIsLoading;
        },

        isBase() {
            return this.publishContainer === 'base';
        },

        isDirty() {
            return this.$dirty.has(this.publishContainer);
        },

        activeLocalization() {
            return _.findWhere(this.localizations, { active: true });
        },

        originLocalization() {
            return _.findWhere(this.localizations, { origin: true });
        },

        computedBreadcrumbs() {
            return {
                'url': this.breadcrumbs[0].url,
                'text': this.breadcrumbs[0].text
            }
        }

    },

    watch: {

        saving(saving) {
            this.$progress.loading(`${this.publishContainer}-defaults-publish-form`, saving);
        }

    },

    methods: {

        clearErrors() {
            this.error = null;
            this.errors = {};
        },

        save() {
            if (!this.canSave) return;

            this.saving = true;
            this.clearErrors();

            const payload = { ...this.visibleValues, ...{
                blueprint: this.fieldset.handle,
                _localized: this.localizedFields,
            }};

            this.$axios[this.method](this.actions.save, payload).then(response => {
                this.saving = false;
                if (!this.isCreating) this.$toast.success(__('Saved'));
                this.$refs.container.saved();
                this.$nextTick(() => this.$emit('saved', response));
            }).catch(e => this.handleAxiosError(e));
        },

        handleAxiosError(e) {
            this.saving = false;

            if (e.response && e.response.status === 422) {
                const { message, errors } = e.response.data;
                this.error = message;
                this.errors = errors;
                this.$toast.error(message);
            } else {
                this.$toast.error(__('Something went wrong'));
            }
        },

        localizationSelected(localization) {
            if (localization.active) return;

            if (this.isDirty) {
                if (! confirm(__('Are you sure? Unsaved changes will be lost.'))) {
                    return;
                }
            }

            this.$dirty.remove(this.publishContainer);

            this.localizing = localization.handle;

            if (this.isBase) {
                window.history.replaceState({}, '', localization.url);
            }

            this.$axios.get(localization.url).then(response => {
                const data = response.data;
                this.values = data.values;
                this.originValues = data.originValues;
                this.originMeta = data.originMeta;
                this.meta = data.meta;
                this.localizations = data.localizations;
                this.localizedFields = data.localizedFields;
                this.hasOrigin = data.hasOrigin;
                this.actions = data.actions;
                this.fieldset = data.blueprint;
                this.isRoot = data.isRoot;
                this.site = localization.handle;
                this.localizing = false;
                this.$nextTick(() => this.$refs.container.clearDirtyState());
            })
        },

        setFieldValue(handle, value) {
            if (this.hasOrigin) this.desyncField(handle);

            this.$refs.container.setFieldValue(handle, value);
        },

        syncField(handle) {
            if (! confirm(__('Are you sure? This field\'s value will be replaced by the value in the original entry.')))
                return;

            this.localizedFields = this.localizedFields.filter(field => field !== handle);
            this.$refs.container.setFieldValue(handle, this.originValues[handle]);

            // Update the meta for this field. For instance, a relationship field would have its data preloaded into it.
            // If you sync the field, the preloaded data would be outdated and an ID would show instead of the titles.
            this.meta[handle] = this.originMeta[handle];
        },

        desyncField(handle) {
            if (!this.localizedFields.includes(handle))
                this.localizedFields.push(handle);

            this.$refs.container.dirty();
        },

    },

    mounted() {
        this.$keys.bindGlobal(['mod+s'], e => {
            e.preventDefault();
            this.save();
        });
    },

    created() {
        window.history.replaceState({}, document.title, document.location.href.replace('created=true', ''));
    }

}
</script>
