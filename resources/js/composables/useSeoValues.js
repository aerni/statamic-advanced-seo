import { ANTLERS_PATTERN } from '../utils/antlers.js';
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
 * Strip HTML tags from a string.
 */
function stripTags(html) {
    return html.replace(/<[^>]*>/g, '');
}

/**
 * Extract plain text from a ProseMirror (Bard) JSON structure.
 * Iterative traversal collecting text nodes.
 */
function extractBardText(value) {
    if (typeof value === 'string') {
        return stripTags(value);
    }

    if (!Array.isArray(value)) return '';

    let text = '';

    const queue = [...value];

    while (queue.length > 0) {
        const item = queue.shift();

        if (!item?.type) continue;

        if (item.type === 'text') {
            text += ` ${item.text || ''}`;
        }

        queue.unshift(...(item.content ?? []));
    }

    return text.trim();
}

/**
 * Convert a field value to plain text based on the field type.
 */
function toPlainText(value, fieldType) {
    switch (fieldType) {
        case 'markdown':
            return stripTags(markdown(value));
        case 'bard':
            return extractBardText(value);
        default:
            return typeof value === 'string' ? value : '';
    }
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
     * Resolve a blueprint field value to plain text.
     */
    function resolveFieldValue(handle) {
        const field = getField(handle);

        if (!field) return;

        return field.type === 'seo'
            ? resolve(handle)
            : toPlainText(getFieldRawValue(handle), field.type);
    }

    /**
     * Find a token value from field meta.
     * Checks meta.tokens (standalone) and meta.meta.tokens (seo-wrapped).
     */
    function resolveTokenValue(handle) {
        return Object.values(fields.value)
            .flatMap(field => getFieldMeta(field.handle))
            .flatMap(meta => meta?.tokens ?? meta?.meta?.tokens ?? [])
            .find(token => token.handle === handle)
            ?.value;
    }

    /**
     * Replace {{ handle }} references in a string with resolved values.
     * Unresolvable references are kept as raw Antlers.
     */
    function resolveAntlers(value) {
        if (!value || typeof value !== 'string') return value;

        return value.replace(new RegExp(ANTLERS_PATTERN.source, 'g'), (match, handle) => {
            return resolveFieldValue(handle) ?? resolveTokenValue(handle) ?? match;
        });
    }

    /**
     * Resolve a seo field value, using the cascade default
     * for reactive resolution when the field is in inherited state.
     */
    function resolveDefault(handle) {
        const def = getFieldMeta(handle)?.defaultValue;
        return typeof def === 'string' && def !== '' ? resolveAntlers(def) : '';
    }

    /**
     * Resolve a seo field value with circular reference protection.
     * Circular references return undefined so they fall through
     * to raw Antlers in the preview, matching unknown-handle behavior.
     */
    function resolve(handle) {
        if (resolving.has(handle)) return;

        resolving.add(handle);

        try {
            const value = getFieldRawValue(handle);
            const field = getField(handle);

            switch (value?.source) {
                case 'default':
                    return resolveDefault(handle);
                case 'custom':
                    return resolveAntlers(value.value)?.trim();
                default:
                    return toPlainText(value, field.type);
            }
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
