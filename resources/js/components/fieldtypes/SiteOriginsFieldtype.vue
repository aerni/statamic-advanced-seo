<script setup>
import { Fieldtype } from '@statamic/cms';
import { Heading, Select } from '@statamic/cms/ui';

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
        .filter((s) => !s.readonly || s.handle === site.origin)
        .map((s) => ({ value: s.handle, label: __(s.label) }))
        .filter((s) => !circularSites.has(s.value));
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
                <td class="grid-cell" :class="{ 'text-gray-500': site.readonly }">
                    <Heading :text="__(site.label)" :icon="site.readonly ? 'padlock-locked' : null" />
                </td>
                <td class="grid-cell">
                    <Select
                        class="w-full"
                        :options="originOptions(site)"
                        :clearable="!site.readonly"
                        :disabled="site.readonly"
                        :model-value="site.origin"
                        @update:model-value="(newOrigin) => {
                            const updatedValue = props.value.map(s =>
                                s.handle === site.handle ? { ...s, origin: newOrigin } : s
                            );
                            update(updatedValue);
                        }"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>
