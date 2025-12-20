<script setup>
import { onMounted, onUnmounted, ref, useTemplateRef, computed, nextTick, getCurrentInstance } from 'vue';
import { DocsCallout, Header, Button, PublishContainer } from '@statamic/cms/ui';
import { Pipeline, Request, BeforeSaveHooks, AfterSaveHooks } from '@statamic/cms/save-pipeline';
import { Head } from '@statamic/cms/inertia';
import SiteSelector from '../../components/SiteSelector.vue';

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const props = defineProps({
	title: String,
	icon: String,
	blueprint: Object,
    initialReference: String,
    initialValues: Object,
	initialMeta: Object,
    initialLocalizations: Array,
    initialLocalizedFields: Array,
    initialSite: String,
    action: String,
    configUrl: String,
    readOnly: Boolean,
    // docs: Array,
});

const container = useTemplateRef('container');
const reference = ref(props.initialReference);
const values = ref(props.initialValues);
const meta = ref(props.initialMeta);
const errors = ref({});
const localizing = ref(false);
const localizations = ref(props.initialLocalizations);
const localizedFields = ref(props.initialLocalizedFields);
const site = ref(props.initialSite);
const pendingLocalization = ref(null);
const saving = ref(false);

function save() {
	new Pipeline()
		.provide({ container, errors, saving })
		.through([
			new BeforeSaveHooks('seo-defaults-config'),
			new Request(props.action, 'patch', {
				site: site.value,
				_localized: localizedFields.value,
			}),
			new AfterSaveHooks('seo-defaults-config'),
		])
		.then((response) => {
			Statamic.$toast.success(__('Saved'));
		});
}

let saveKeyBinding;

onMounted(() => {
	saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], (e) => {
        e.preventDefault();
		if (!canSave.value) return;
		save();
	});
});

onUnmounted(() => saveKeyBinding.destroy());

const isDirty = computed(() => Statamic.$dirty.has('seo-defaults-config'))
const canSave = computed(() => !props.readOnly && isDirty.value && !saving.value);
const showLocalizationSelector = computed(() => localizations.value.length > 1);

const localizationSelected = (localizationHandle) => {
	let localization = localizations.value.find((localization) => localization.handle === localizationHandle);

	if (localization.active) return;

	if (isDirty.value) {
		pendingLocalization.value = localization;
		return;
	}

	switchToLocalization(localization);
};

const confirmSwitchLocalization = () => {
	switchToLocalization(pendingLocalization.value);
	pendingLocalization.value = null;
};

const updateDataFromResponse = (data) => {
	reference.value = data.initialReference;
	values.value = data.initialValues;
	meta.value = data.initialMeta;
	localizations.value = data.initialLocalizations;
	localizedFields.value = data.initialLocalizedFields;
};

const switchToLocalization = (localization) => {
	localizing.value = localization.handle;

	window.history.replaceState({}, '', localization.url);

	$axios.get(localization.url).then((response) => {
		updateDataFromResponse(response.data);
		site.value = localization.handle;
		localizing.value = false;
		nextTick(() => container.value.clearDirtyState());
	});
};
</script>
<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title :icon>
            <SiteSelector
				v-if="showLocalizationSelector"
				:sites="localizations"
				:model-value="site"
				@update:modelValue="localizationSelected"
			/>

            <Button variant="primary" :text="__('Save')" @click="save" :disabled="!canSave" />
        </Header>

        <PublishContainer
            ref="container"
            name="seo-defaults-config"
            :reference
            :blueprint
            v-model="values"
            :meta
            :errors
            :site
            v-model:modified-fields="localizedFields"
            :track-dirty-state="true"
            :read-only
            :as-config="true"
        />

        <confirmation-modal
			v-if="pendingLocalization"
			:title="__('Unsaved Changes')"
			:body-text="__('Are you sure? Unsaved changes will be lost.')"
			:button-text="__('Continue')"
			:danger="true"
			@confirm="confirmSwitchLocalization"
			@cancel="pendingLocalization = null"
		/>

        <!-- <DocsCallout :topic="__(docs.topic)" :url="docs.url" /> -->
    </div>
</template>
