<script setup>
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { PublishContainer, PublishTabs, Header, Button, Panel, Heading, Switch, StatusIndicator, Dropdown, DropdownMenu, DropdownItem, DropdownSeparator } from '@statamic/cms/ui';
import { Pipeline, Request } from '@statamic/cms/save-pipeline';
import { Head, router } from '@statamic/cms/inertia';

const props = defineProps({
    id: { type: String, default: null },
    title: { type: String, required: true },
    blueprint: { type: Object, required: true },
    values: { type: Object, required: true },
    meta: { type: Object, required: true },
    enabled: { type: Boolean, default: true },
    submitUrl: { type: String, required: true },
    testUrl: { type: String, default: null },
    itemActions: { type: Array, default: () => [] },
    itemActionUrl: { type: String, default: null },
    hits: { type: Object, default: null },
    createdAt: { type: String, default: null },
});

const container = useTemplateRef('container');
const values = ref(props.values);
const meta = ref(props.meta);
const enabled = ref(props.enabled);
const errors = ref({});
const saving = ref(false);

const heading = props.values.source ?? props.title;

const hasItemActions = computed(() => props.itemActions.length > 0);

function actionCompleted(successful, response = {}) {
    if (successful === false) {
        Statamic.$toast.error(response.message || __('Action failed'));

        return;
    }

    if (response.message !== false) {
        Statamic.$toast.success(response.message || __('Action completed'));
    }

    if (response.redirect) {
        router.visit(response.redirect);

        return;
    }

    router.reload();
}

const saveText = computed(() => {
    if (enabled.value === props.enabled) {
        return __('Save');
    }

    return enabled.value
        ? __('advanced-seo::messages.save_and_enable')
        : __('advanced-seo::messages.save_and_disable');
});

function save() {
    new Pipeline()
        .provide({ container, errors, saving })
        .through([
            new Request(props.submitUrl, 'patch', { enabled: enabled.value }),
        ])
        .then((response) => {
            Statamic.$toast.success(__('Saved'));
            router.get(response.data.redirect);
        });
}

let saveKeyBinding;

onMounted(() => {
    saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], (e) => {
        e.preventDefault();
        save();
    });
});

onUnmounted(() => saveKeyBinding.destroy());
</script>

<template>
    <Head :title="heading" />

    <Header>
        <template #title>
            <StatusIndicator :status="props.enabled ? 'published' : 'draft'" />
            {{ heading }}
        </template>
        <ItemActions
            v-if="hasItemActions"
            :item="id"
            :url="itemActionUrl"
            :actions="itemActions"
            @completed="actionCompleted"
            v-slot="{ actions }"
        >
            <Dropdown>
                <template #trigger>
                    <Button icon="dots" variant="ghost" :aria-label="__('Open dropdown menu')" />
                </template>
                <DropdownMenu>
                    <DropdownItem
                        v-for="action in actions.filter((action) => !action.dangerous)"
                        :key="action.handle"
                        :text="__(action.title)"
                        :icon="action.icon"
                        @click="action.run"
                    />
                    <DropdownSeparator v-if="actions.some((action) => action.dangerous) && actions.some((action) => !action.dangerous)" />
                    <DropdownItem
                        v-for="action in actions.filter((action) => action.dangerous)"
                        :key="action.handle"
                        :text="__(action.title)"
                        :icon="action.icon"
                        variant="destructive"
                        @click="action.run"
                    />
                </DropdownMenu>
            </Dropdown>
        </ItemActions>
        <Button
            v-if="testUrl && props.enabled && Number(values.response_code) !== 410"
            :href="testUrl"
            :text="__('advanced-seo::messages.test_redirect')"
            icon="external-link"
            target="_blank"
            rel="noopener noreferrer"
        />
        <Button variant="primary" :text="saveText" :disabled="saving" @click="save" />
    </Header>

    <PublishContainer
        ref="container"
        name="redirect-edit"
        :blueprint
        :meta
        :errors
        v-model="values"
    >
        <PublishTabs>
            <template #actions>
                <Panel class="px-5! py-3! dark:bg-gray-800!">
                    <div class="flex items-center justify-between">
                        <Heading :text="__('advanced-seo::fields.redirect_enabled.display')" />
                        <Switch v-model="enabled" />
                    </div>
                    <dl v-if="hits || createdAt" class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 text-sm">
                        <template v-if="hits">
                            <dt class="text-gray-600/90 dark:text-gray-400">{{ __('advanced-seo::messages.redirect_hits') }}</dt>
                            <dd class="text-right tabular-nums text-gray-925 dark:text-gray-300">{{ hits.count }}</dd>
                            <template v-if="hits.last_hit_at">
                                <dt class="text-gray-600/90 dark:text-gray-400">{{ __('advanced-seo::messages.redirect_last_hit_at') }}</dt>
                                <dd class="text-right text-gray-925 dark:text-gray-300"><date-time :of="hits.last_hit_at" /></dd>
                            </template>
                        </template>
                        <template v-if="createdAt">
                            <dt class="text-gray-600/90 dark:text-gray-400">{{ __('advanced-seo::messages.redirect_created_at') }}</dt>
                            <dd class="text-right text-gray-925 dark:text-gray-300"><date-time :of="createdAt" /></dd>
                        </template>
                    </dl>
                </Panel>
            </template>
        </PublishTabs>
    </PublishContainer>
</template>
