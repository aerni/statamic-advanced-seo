<script setup>
import { onMounted, onUnmounted, ref, useTemplateRef, computed, nextTick, getCurrentInstance } from 'vue';
import { DocsCallout, Header, Dropdown, DropdownMenu, DropdownItem, Button, PublishContainer } from '@statamic/cms/ui';
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
    initialHasOrigin: Boolean,
	initialOriginValues: Object,
	initialOriginMeta: Object,
    initialSite: String,
    initialConfigUrl: String,
    action: String,
    readOnly: Boolean,
    configurable: Boolean,
});

const container = useTemplateRef('container');
const reference = ref(props.initialReference);
const values = ref(props.initialValues);
const meta = ref(props.initialMeta);
const errors = ref({});
const localizing = ref(false);
const localizations = ref(props.initialLocalizations);
const localizedFields = ref(props.initialLocalizedFields);
const hasOrigin = ref(props.initialHasOrigin);
const originValues = ref(props.initialOriginValues);
const originMeta = ref(props.initialOriginMeta);
const site = ref(props.initialSite);
const configUrl = ref(props.initialConfigUrl);
const syncFieldConfirmationText = ref(__('messages.sync_entry_field_confirmation_text'));
const pendingLocalization = ref(null);
const saving = ref(false);

function save() {
	new Pipeline()
		.provide({ container, errors, saving })
		.through([
			new BeforeSaveHooks('seo-defaults'),
			new Request(props.action, 'patch', {
				site: site.value,
				_localized: localizedFields.value,
			}),
			new AfterSaveHooks('seo-defaults'),
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

const isDirty = computed(() => Statamic.$dirty.has('seo-defaults'));
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
	originValues.value = data.initialOriginValues;
	originMeta.value = data.initialOriginMeta;
	meta.value = data.initialMeta;
	localizations.value = data.initialLocalizations;
	localizedFields.value = data.initialLocalizedFields;
	hasOrigin.value = data.initialHasOrigin;
	configUrl.value = data.initialConfigUrl;
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

const refreshLocalization = () => {
	const currentLocalization = localizations.value.find((localization) => localization.handle === site.value);

	if (!currentLocalization) return;

	$axios.get(currentLocalization.url).then((response) => {
		updateDataFromResponse(response.data);
		nextTick(() => container.value.clearDirtyState());
	});
};
</script>
<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title :icon>
            <Dropdown v-if="configurable">
				<template #trigger>
					<Button icon="dots" variant="ghost" :aria-label="__('Open dropdown menu')" />
				</template>
				<DropdownMenu>
					<DropdownItem :text="__('Configure')" icon="cog" :href="configUrl" />
				</DropdownMenu>
			</Dropdown>

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
            name="seo-defaults"
            :reference
            :blueprint
            v-model="values"
            :meta
            :errors
            :site
            :origin-values
			:origin-meta
            v-model:modified-fields="localizedFields"
            :sync-field-confirmation-text
            :track-dirty-state="true"
            :read-only
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

        <DocsCallout :topic="__('Site Defaults')" url="https://advanced-seo.michaelaerni.ch/usage/settings-and-defaults" />
    </div>
</template>
