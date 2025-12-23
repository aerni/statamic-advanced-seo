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
        .map((site) => ({ value: site.handle, label: __(site.label) }))
        .filter((site) => !circularSites.has(site.value));
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
                    <div class="flex items-center gap-2">
                        <Heading :text="__(site.label)" />
                    </div>
                </td>
                <td class="grid-cell">
                    <Select
                        class="w-full"
                        :options="originOptions(site)"
                        :clearable="true"
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
