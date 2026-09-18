import { ANTLERS_PATTERN } from '../utils/antlers.js';
import { normalize } from '../utils/normalizers.js';
import { usePublishFields } from './usePublishFields.js';

/**
 * Truncate a string to a maximum length, appending ' ...' if needed.
 */
function truncate(value, max) {
    return value?.length > max
        ? value.substring(0, max) + ' ...'
        : value;
}

/**
 * Composable for resolving SEO field values from the publish container.
 *
 * Provides reactive resolution of field values with support for:
 * - seo fields (default, custom)
 * - Field type conversion (markdown, bard) to plain text
 * - Template resolution ({{ handle }})
 * - Recursive resolution for seo field references
 *
 * @returns {{ resolve: Function, truncate: Function }}
 */
export function useSeoValues() {
    const {
        fields,
        getField,
        getFieldRawValue,
        getFieldMeta,
    } = usePublishFields();

    const resolving = new Set();

    /**
     * Resolve a field token's value to plain text.
     */
    function resolveFieldToken(handle) {
        const field = getField(handle);

        if (!field) return;

        if (field.type === 'seo') return resolve(handle);

        return normalize(field.type, getFieldRawValue(handle), getFieldMeta(handle));
    }

    /**
     * Resolve a value token's pre-computed value from field meta.
     */
    function resolveValueToken(handle) {
        return Object.values(fields.value)
            .flatMap(field => getFieldMeta(field.handle))
            .flatMap(meta => meta?.tokens ?? meta?.meta?.tokens ?? [])
            .find(token => token.handle === handle && 'value' in token)
            ?.value;
    }

    /**
     * Replace {{ handle }} references in a string with resolved values.
     * Unresolvable references are kept as raw Antlers.
     */
    function resolveAntlers(value) {
        if (!value || typeof value !== 'string') return value;

        return value.replace(new RegExp(ANTLERS_PATTERN.source, 'g'), (match, handle) => {
            return resolveFieldToken(handle) ?? resolveValueToken(handle) ?? match;
        });
    }

    /**
     * Resolve a seo field value with circular reference protection.
     * Circular references return undefined so they fall through
     * to raw Antlers in the preview, matching unknown-handle behavior.
     *
     * For wrapped seo fields, value.value is already the right string:
     * origin default when synced, local default after reset, or a custom value.
     */
    function resolve(handle) {
        if (resolving.has(handle)) return;

        resolving.add(handle);

        try {
            const value = getFieldRawValue(handle);
            const field = getField(handle);

            if (value?.source === 'default' || value?.source === 'custom') {
                return typeof value.value === 'string' && value.value !== ''
                    ? resolveAntlers(value.value)?.trim()
                    : '';
            }

            return normalize(field.type, value, getFieldMeta(handle));
        } finally {
            resolving.delete(handle);
        }
    }

    return {
        resolve,
        resolveAntlers,
        truncate,
    };
}
