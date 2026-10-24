<script setup>
import { Fieldtype } from '@statamic/cms';
import { Heading, Icon } from '@statamic/cms/ui';
import LockedSiteSelect from '../ui/LockedSiteSelect.vue';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update } = Fieldtype.use(emit, props);

defineExpose(expose);

function originOptions(site) {
    // Build a set of sites that would create circular dependencies
    const circularSites = new Set([site.handle]);

    // Find all sites that have the current site as their origin (directly or indirectly)
    const findDependents = (siteHandle) => {
        props.value.forEach((site) => {
            if (site.origin === siteHandle && !circularSites.has(site.handle)) {
                circularSites.add(site.handle);
                findDependents(site.handle);
            }
        });
    };

    findDependents(site.handle);

    return props.value
        .filter((s) => !circularSites.has(s.handle))
        .map((s) => ({
            value: s.handle,
            label: __(s.label),
            disabled: s.readonly,
        }));
}

function updateOrigin(site, newOrigin) {
    update(props.value.map((row) => (
        row.handle === site.handle ? { ...row, origin: newOrigin } : row
    )));
}
</script>

<template>
    <table class="grid-table">
        <thead>
            <tr>
                <th scope="col">
                    <div class="flex items-center justify-between">
                        {{ __('Site') }}
                    </div>
                </th>
                <th scope="col">
                    <div class="flex items-center justify-between">
                        {{ __('Origin') }}
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="site in props.value" :key="site.handle">
                <td class="grid-cell">
                    <span class="inline-flex items-center gap-2 text-gray-700 dark:text-white">
                        <Heading :text="__(site.label)" />
                        <Icon
                            v-if="site.readonly"
                            name="padlock-locked"
                            class="size-3.5 shrink-0 opacity-50"
                        />
                    </span>
                </td>
                <td class="grid-cell">
                    <LockedSiteSelect
                        :options="originOptions(site)"
                        :clearable="!site.readonly"
                        :disabled="site.readonly"
                        :model-value="site.origin"
                        @update:model-value="(newOrigin) => updateOrigin(site, newOrigin)"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>
