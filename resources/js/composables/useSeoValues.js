import { usePublishFields } from './usePublishFields.js';

/**
 * Truncate a string to a maximum length, appending ' ...' if needed.
 */
function truncate(value, max) {
    return value?.length > max
        ? value.substring(0, max - 4) + ' ...'
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
 * - Template parsing ({{ handle }})
 * - Recursive resolution for seo field references
 *
 * @returns {{ resolve: Function, truncate: Function }}
 */
export function useSeoValues() {
    const {
        getField,
        getFieldRawValue,
        getFieldMeta,
    } = usePublishFields();

    /**
     * Resolve a field value to plain text. If the field is a seo field,
     * resolve it through the full chain. Otherwise, convert to plain text.
     */
    function resolveFieldValue(handle) {
        const field = getField(handle);
        if (!field) return null;

        if (field.type === 'seo') {
            return resolve(handle) ?? '';
        }

        return toPlainText(getFieldRawValue(handle), field.type);
    }

    /**
     * Replace {{ handle }} references in a string
     * with the corresponding publish field values.
     * Unresolvable references are kept as raw Antlers.
     */
    function parse(value) {
        if (!value || typeof value !== 'string') return value;

        return value
            .replace(/\{\{\s*([a-zA-Z0-9_\-]+)\s*\}\}/g, (match, handle) => {
                const resolved = resolveFieldValue(handle);
                return resolved !== null ? resolved : match;
            });
    }

    /**
     * Resolve a seo field value, using the cascade default
     * for reactive resolution when the field is in inherited state.
     */
    function resolveDefault(handle) {
        const def = getFieldMeta(handle)?.defaultValue;
        return typeof def === 'string' && def !== '' ? parse(def) : '';
    }

    function resolve(handle) {
        const value = getFieldRawValue(handle);
        const field = getField(handle);

        switch (value?.source) {
            case 'default':
                return resolveDefault(handle);
            case 'custom':
                return parse(value.value)?.trim();
            default:
                return toPlainText(value, field.type);
        }
    }

    return {
        resolve,
        parse,
        truncate,
    };
}
